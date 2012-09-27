<?php
/* Metadatum Fixture generated on: 2012-09-24 17:11:08 : 1348506668 */
class MetadatumFixture extends CakeTestFixture {
	var $name = 'Metadatum';
	var $import = array('model' => 'Metadatum');


	var $records = array(
		array(
			'id' => '4d3681e5-f078-4914-a7ea-030cf67883f5',
			'parent_id' => NULL,
			'model' => 'User',
			'foreign_id' => '12345678-1111-0000-0000-venice------',
			'name' => 'lightbox',
			'value' => NULL,
			'lft' => '1',
			'rght' => '4',
			'created' => '2011-01-19 06:17:09',
			'modified' => '2011-01-19 06:17:09'
		),
		array(
			'id' => '4d3681e5-95cc-4702-823d-030cf67883f5',
			'parent_id' => '4d3681e5-f078-4914-a7ea-030cf67883f5',
			'model' => 'User',
			'foreign_id' => '12345678-1111-0000-0000-venice------',
			'name' => 'regionAsJSON',
			'value' => '{"1":688.11669921875,"top":688.11669921875,"0":0.399993896484375,"left":0.399993896484375,"bottom":790.11669921875,"right":853.3999938964844,"width":853,"height":102}',
			'lft' => '2',
			'rght' => '3',
			'created' => '2011-01-19 06:17:09',
			'modified' => '2011-01-19 06:17:09'
		),
	);
}
