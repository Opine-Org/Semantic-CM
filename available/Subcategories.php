<?php
/*
 * @version 2
 * @link https://raw.github.com/Opine-Org/Semantic-CM/master/available/Subcategories.php
 * @mode upgrade
 *
 * .3 wrong field referenced
 * .4 set correct lable for embedded doc
 * .5 delete feature
 * .6 fix html
 * .7 definition and description for count added
 * .8 named attributes
 */
namespace Manager;

class Subcategories
{
    public $collection = 'Collection\Categories';
    public $title = 'Subcategories';
    public $titleField = 'title';
    public $singular = 'Subcategory';
    public $description = '{{count}} subcategories';
    public $definition = 'Coming Soon';
    public $acl = ['content', 'admin', 'superadmin'];
    public $icon = 'browser';
    public $category = 'Content';
    public $after = 'function';
    public $function = 'embeddedUpsert';
    public $embedded = true;

    public function titleField()
    {
        return [
            'name'          => 'title',
            'label'         => 'Title',
            'required'      => false,
            'display'       => 'Field\InputText'
        ];
    }

    public function imageField()
    {
        return [
            'name'          => 'image',
            'label'         => 'Image',
            'display'       => 'Field\InputFile'
        ];
    }

    public function indexPartial()
    {
        $partial = <<<'HBS'
            {{{ManagerEmbeddedIndexHeader label="Subcategories"}}}
            {{#if subcategory}}
                <table class="ui table manager segment sortable">
                    <col width="10%">
                    <col width="80%">
                    <col width="10%">
                    <thead>
                        <tr>
                            <th><i class="shuffle basic icon"></i></th>
                            <th>Title</th>
                            <th class="trash">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{#each subcategory}}
                            <tr data-id="{{dbURI}}">
                                <td class="handle"><i class="reorder icon"></i></td>
                                <td>{{title}}</td>
                                <td><div class="manager trash ui icon button"><i class="trash icon small"></i></div></td>
                            </tr>
                        {{/each}}
                    </tbody>
                </table>
            {{else}}
                {{{ManagerEmbeddedIndexEmpty singular="Subcategory"}}}
            {{/if}}
HBS;

        return $partial;
    }

    public function formPartial()
    {
        $partial = <<<'HBS'
            {{{ManagerEmbeddedFormHeader metadata=metadata}}}
                {{{ManagerField . name="title" label="Title" required="true"}}}
                {{{ManagerField . name="image" label="Image"}}}
                {{{id}}}
                {{{form-token}}}
            {{{ManagerEmbeddedFormFooter}}}
HBS;

        return $partial;
    }
}
