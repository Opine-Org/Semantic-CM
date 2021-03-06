<?php
/**
 * Opine\Mangager\Model
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine\Manager;

use Exception;
use Symfony\Component\Yaml\Yaml;

class Model
{
    private $root;
    private $manager;
    private $bundleModel;
    private $db;
    private $cacheFile;
    private $collectionModel;
    private $collectionService;
    private $postService;

    public function __construct($root, $manager, $db, $bundleModel, $collectionService, $collectionModel, $postService, $personService)
    {
        $this->root = $root;
        $this->manager = $manager;
        $this->bundleModel = $bundleModel;
        $this->db = $db;
        $this->collectionService = $collectionService;
        $this->collectionModel = $collectionModel;
        $this->cacheFile = $this->root.'/../var/cache/managers.json';
        $this->postService = $postService;
        $this->personService = $personService;
    }

    public function cacheWrite(Array $managers)
    {
        $json = json_encode(['managers' => $managers], JSON_PRETTY_PRINT);
        file_put_contents($this->cacheFile, $json);

        return $json;
    }

    public function cacheRead()
    {
        return json_decode(file_get_contents($this->cacheFile), true)['managers'];
    }

    public function managerGetByLink($slug)
    {
        $managers = $this->cacheRead();
        if (!isset($managers[$slug])) {
            throw new Exception('can not get manager by slug: '.$slug);
        }

        return $managers[$slug];
    }

    public function collection($collectionName)
    {
        $collections = $this->collectionModel->collections();
        if (!isset($collections[$collectionName])) {
            throw new Exception('Collection not found: '.$collectionName);
        }
        return $collections[$collectionName];
    }


    public function collectionCounts()
    {
        $countsTemp = $this->db->collection('collection_stats')->find();
        $counts = [];
        foreach ($countsTemp as $count) {
            $counts[$count['collection']] = $count;
        }

        return $counts;
    }

    public function delete($dbURI)
    {
        $result = $this->db->document($dbURI)->remove();
        if (isset($result['ok']) && $result['ok'] == 1) {
            return true;
        }

        return false;
    }

    public function post($context)
    {
        $slug = $context['formObject']->manager['slug'];
        if (!isset($context['dbURI']) || empty($context['dbURI'])) {
            throw new Exception('Context does not contain a dbURI');
        }
        $document = $this->postService->get($slug);
        if ($document === false || empty($document)) {
            throw new Exception('Document not found in post');
        }
        $documentInstance = $this->db->document($context['dbURI'], $document);
        $documentInstance->upsert();
        $this->postService->statusSaved();
        $document = $documentInstance->current();
        $id = $documentInstance->id();
        $collectionName = $documentInstance->collection();
        $collectionInstance = $this->collectionService->factory($collectionName);
        if ($collectionInstance === false) {
            return;
        }
        $managerUrl = '/Manager/item/'.$slug.'/'.$context['dbURI'];
        $collectionInstance->indexSearch($id, $document, $managerUrl);
        $collectionInstance->views('upsert', $id, $document);
        $collectionInstance->statsUpdate($context['dbURI']);
    }

    public function loggedIn()
    {
        return $this->personService->available();
    }

    public function logout()
    {
        $this->personService->logout();
    }

    public function authCheckPath($uri)
    {
        $parts = explode('/', trim($uri, '/'));

        //login & logout
        if ($uri == '/Manager/login' || $uri == '/Manager/logout') {
            return true;
        }

        //dashboard
        if ($uri == '/Manager' || substr_count($uri, '/Manager/section') == 1 || substr_count($uri, '/Manager/api') == 1) {
            if ($this->personService->inGroupLike('/^manager/i')) {
                return true;
            }

            return false;
        }

        //index
        if (substr_count($uri, '/Manager/index/') == 1) {
            return $this->authGroupsForManager($parts[2]);
        }

        //item
        if (substr_count($uri, '/Manager/item/') == 1) {
            return $this->authGroupsForManager($parts[2]);
        }

        return false;
    }

    private function authGroupsForManager($slug)
    {
        $metadata = $this->managerGetByLink($slug);

        return $this->authManagerCheck($metadata);
    }

    public function authManagerCheck(&$metadata)
    {
        return $this->personService->permission([
            'manager',
            'manager-category-'.$metadata['category'],
            'manager-'.$metadata['category'].'-'.$metadata['link'],
        ]);
    }

    public function authenticate($context)
    {
        if (!isset($context['dbURI']) || empty($context['dbURI'])) {
            throw new Exception('Context does not contain a dbURI');
        }
        if (!isset($context['formMarker'])) {
            throw new Exception('Form marker not set in post');
        }
        $document = $this->postService->get($context['formMarker']);
        if ($document === false || empty($document)) {
            throw new Exception('Document not found in post');
        }
        if (!isset($document['route'])) {
            $this->postService->errorFieldSet($context['formMarker'], 'Missing url.');

            return;
        }
        $try = $this->personService->login($document['email'], $document['password']);
        if ($try === false) {
            $this->postService->errorFieldSet($context['formMarker'], 'Credentials do not match. Please check your email or password and try again.');

            return;
        }
        $person = $this->personService->get();
        $this->postService->responseFieldsSet(['api_token' => (string) $person['api_token']]);
        $this->postService->statusSaved();
    }

    public function build()
    {
        $bundles = $this->bundleModel->bundles();
        $namespacesByPath = [
            '/../config/managers' => '',
        ];
        $searchPaths = [
            '/../config/managers',
        ];
        $bundleByPath = [
            '/../config/managers' => null,
        ];
        foreach ($bundles as $bundle) {
            if (!isset($bundle['root'])) {
                continue;
            }
            $searchPath = $bundle['root'].'/../config/managers';
            $namespacesByPath[$searchPath] = $bundle['name'].'\Manager';
            $bundleByPath[$searchPath] = $bundle['name'];
        }
        $managers = [];
        if (!file_exists($this->cacheFile)) {
            @mkdir($managersRoot);
            $this->cacheWrite(['managers' => $managers]);
        }
        foreach ($searchPaths as $searchPath) {
            $managersRoot = $this->root.$searchPath;
            if (!file_exists($managersRoot)) {
                continue;
            }
            $dirFiles = glob($managersRoot.'/*.yml');
            if (!is_array($dirFiles) || empty($dirFiles)) {
                continue;
            }
            foreach ($dirFiles as $managerPath) {
                $managerInstance = $this->yaml($managerPath)['manager'];
                $manager = $managerInstance['slug'];
                $groups = ['manager', 'manager-'.$managerInstance['category'], 'manager-specific-'.$manager];
                $collection = $managerInstance['collection'];
                $collectionName = '';
                if (class_exists($collection)) {
                    $collectionInstance = $this->collectionService->factory($collection);
                    $collectionName = $collectionInstance->collection;
                }
                if (isset($managerInstance['formPartial'])) {
                    $dst = $this->root.'/partials/Manager/forms/'.$managerInstance['slug'].'.hbs';
                    if (!file_exists(dirname($dst))) {
                        @mkdir(dirname($dst), 0777, true);
                    }
                    file_put_contents($dst, $managerInstance['formPartial']);
                    unset($managerInstance['formPartial']);
                }
                if (isset($managerInstance['indexPartial'])) {
                    $dst = $this->root.'/partials/Manager/indexes/'.$managerInstance['slug'].'.hbs';
                    if (!file_exists(dirname($dst))) {
                        @mkdir(dirname($dst), 0777, true);
                    }
                    file_put_contents($dst, $managerInstance['indexPartial']);
                    unset($managerInstance['indexPartial']);
                }
                foreach ($managerInstance['fields'] as $key => &$value) {
                    $value['name'] = $key;
                    $value['marker'] = $managerInstance['slug'];
                }
                $managers[$managerInstance['slug']] = array_merge($managerInstance, [
                    'manager' => $manager,
                    'collection_name' => $collectionName,
                    'link' => $managerInstance['slug'],
                    'bundle' => $bundleByPath[$searchPath],
                    'name' => $managerInstance['slug'],
                ]);
            }
        }

        return $this->cacheWrite($managers);
    }

    public function sort($post)
    {
        $sample = $post['sorted'][0];
        $depth = substr_count($sample, ':');
        if ($depth == 1) {
            $offset = 1;
            foreach ($post['sorted'] as $dbURI) {
                $parts = explode(':', $dbURI);
                $this->db->collection($parts[0])->update([
                        '_id' => $this->db->id($parts[1]),
                    ], [
                        '$set' => [
                            'sort_key' => $offset,
                        ]
                    ]);
                $offset++;
            }
            echo json_encode(['success' => true]);

            return;
        } else {
            $parts = explode(':', $sample);
            $dbURI = $parts[0].':'.$parts[1];
            $documentInstance = $this->db->document($dbURI);
            $document = $documentInstance->current();
            if ($depth == 3) {
                $embedded = $parts[($depth - 1)];
                $newDocument = [];
                foreach ($post['sorted'] as $dbURIEmbedded) {
                    foreach ($document[$embedded] as $embeddedDocument) {
                        if ($dbURIEmbedded == $embeddedDocument['dbURI']) {
                            $newDocument[] = $embeddedDocument;
                            continue;
                        }
                    }
                }
                $document[$embedded] = $newDocument;
                $this->db->document($dbURI, $document)->upsert();
                echo json_encode(['success' => true]);

                return;
            } elseif ($depth == 5) {
                $embedded = $parts[($depth - 3)];
                $embeddedId = $parts[($depth - 2)];
                $embedded = $parts[($depth - 1)];
            } else {
                echo 'Wow!  That is a deep sort!';
            }
        }
    }

    private function yaml($file)
    {
        try {
            return Yaml::parse(file_get_contents($file));
        } catch (Exception $e) {
            throw new Exception('Can not parse file: '.$file.', '.$e->getMessage());
        }
    }
}
