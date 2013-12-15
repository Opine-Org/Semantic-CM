<?php
/*
 * @version .4
 * @link https://raw.github.com/virtuecenter/manager/master/available/menu_links.php
 * @mode upgrade
 *
 * .3 trash in list view
 * .4 small delete button
 */
namespace Manager;

class menu_links {
	private $field = false;
	public $collection = 'menus_links';
	public $title = 'Menus';
	public $titleField = 'title';
	public $singular = 'Menu';
	public $description = '4 menulinks';
	public $definition = '';
	public $acl = ['content', 'admin', 'superadmin'];
	public $icon = 'browser';
	public $category = 'Content';
	public $after = 'function';
	public $function = 'embeddedUpsert';
	public $embedded = true;
	public $storage = [
		'collection' => 'menus',
		'key' => '_id'
	];

	public function __construct ($field=false) {
		$this->field = $field;
	}

	function urlField () {
		return [
			'name'		=> 'url',
			'label'		=> 'URL',
			'required'	=> false,
			'display'	=> 'InputText'
		];
	}

	function titleField () {
		return [
			'name'		=> 'title',
			'label'		=> 'Title',
			'required'	=> false,
			'display'	=> 'InputText'
		];
	}
	
	function targetField () {
		return [
			'name'		=> 'target',
			'label'		=> 'Redirect',
			'required'	=> true,
			'options'	=> [
				'_self'		=> 'Self',
				'_blank'	=> 'Blank',
				'_top'		=> 'Top',
				'_parent'	=> 'Parent'
			],
			'display'	=> 'Select',
			'nullable'	=> false,
			'default'	=> 'self'
		];
	}
	
	function imageField () {
		return [
			'name' => 'file',
			'label' => 'Image',
			'display' => 'InputFile'
		];
	}

	public function tablePartial () {
		$partial = <<<'HBS'
			{{#EmbeddedCollectionHeader SubMenu}}{{/EmbeddedCollectionHeader}}
			{{#if link}}
				<table class="ui table manager segment sortable">
					<thead>
						<tr>
							<th><i class="shuffle basic icon"></i></th>
							<th>Label</th>
							<th class="trash">Delete</th>
						</tr>
					</thead>
					<tbody>
						{{#each link}}
							<tr data-id="{{dbURI}}">
								<td class="handle"><i class="reorder icon"></i></td>
								<td>{{title}}</td>
								<td><div class="manager trash ui icon button"><i class="trash icon small"></i></div></td>
							</tr>
						{{/each}}
					</tbody>
				</table>
			{{else}}
				{{#EmbeddedCollectionEmpty submenu}}{{/EmbeddedCollectionEmpty}}
			{{/if}}
HBS;
		return $partial;
	}

	public function formPartial () {
		$partial = <<<'HBS'
		    {{#EmbeddedHeader}}{{/EmbeddedHeader}}

		        {{#FieldFull title Title}}{{/FieldFull}}

			    {{#FieldFull url URL}}{{/FieldFull}}

			    {{#FieldFull target Target}}{{/FieldFull}}

			    {{{id}}}

			{{#EmbeddedFooter}}{{/EmbeddedFooter}}
		
HBS;
		return $partial;
	}
}