<?php


class EditorDraft{

	var $slug = 'pl-draft';

	function __construct( ){

		//$this->mode = 'draft';

		$this->mode = $this->editor_mode();


	}

	function editor_mode(){
		$current_user = wp_get_current_user();

		if(isset($_GET['edtr']) && $_GET['edtr'] != ''){

			$state = ($_GET['edtr'] == 'on') ? 'on' : 'off';

			update_user_meta($current_user->ID, 'pl_editor_state', $state);

		} else {

			$state = get_user_meta($current_user->ID, 'pl_editor_state', true);
		}


		if( current_user_can('edit_themes') && $state != 'off')
			return 'draft';
		else {
			return 'live';
		}
	}

	function show_editor(){
		if(current_user_can('edit_themes') && $this->mode == 'draft')
			return true;
		else
			return false;
	}

	function save_draft( $pageID, $typeID, $pageData ){

		if( isset($pageData['global']) )
			pl_settings_update( stripslashes_deep( $pageData['global'] ), 'draft');

		if( isset($pageData['local']) )
			pl_settings_update( $pageData['local'], 'draft', $pageID );

		if( isset($pageData['type']) && $pageID != $typeID)
			pl_settings_update( $pageData['type'], 'draft', $typeID );

	}

	function set_state( $draft_state, $live_state, $metaID = false ){

		$modified = ( $live_state != $draft_state ) ? true : false;

		if($modified)
			echo $metaID;

		if( $metaID )
			pl_meta_update( $metaID, $this->slug, $modified  );
		else
			pl_opt_update( $this->slug, $modified );

	}

	function publish( $pageID, $typeID, EditorMap $map ){

		pl_publish_settings($pageID, $typeID);

		$map->publish_map( $pageID );

		$this->reset_state( $pageID );
		
		do_action( 'extend_flush' );
	}

	function revert( $data, EditorMap $map ){
		$revert = $data['revert'];
		$pageID = $data['pageID'];
		$typeID = $data['typeID'];

		if( $revert == 'local' || $revert == 'all')
			$this->revert_local($pageID, $map);

		if( $revert == 'type' || $revert == 'all')
			$this->revert_type($typeID, $map);

		if( $revert == 'global' || $revert == 'all')
			$this->revert_global($map);


	}

	function revert_local( $pageID, $map ){
		$map->revert_local( $pageID );
		pl_revert_settings( $pageID );
		pl_meta_update( $pageID, $this->slug, false );
	}

	function revert_type( $typeID ){
		pl_revert_settings( $typeID );
		pl_meta_update( $typeID, $this->slug, false );
	}

	function revert_global( $map ){
		$map->revert_global( );
		pl_revert_settings( );
		pl_opt_update( $this->slug, false );
	}



	function get_state( $pageID, $typeID, $map ){

		$state = array();
		$settings = array();
		$default = array('live'=> array(), 'draft' => array());


		// Local
		$settings['local'] = pl_meta( $pageID, PL_SETTINGS );

		if($typeID != $pageID)
			$settings['type'] = pl_meta( $typeID, PL_SETTINGS );

		$settings['global'] = pl_opt( PL_SETTINGS );
		
		$settings['map-local'] = $map->map_local( $pageID );
		$settings['map-global'] = $map->map_global();

		foreach( $settings as $scope => $set ){

			$set = wp_parse_args($set, $default);

			$scope = str_replace('map-', '', $scope);

			if( $set['draft'] != $set['live'] ){
				$state[$scope] = $scope;
			}

		}
		
		if( count($state) > 1 )
			$state[] = 'multi';
		
		return $state;

	}

	function reset_state( $pageID ){
		pl_meta_update( $pageID, $this->slug, false );
		pl_opt_update( $this->slug, false );
	}

	function set_local( $pageID, $val = true ){
		pl_meta_update( $pageID, $this->slug, $val );
	}

	function set_global( $val = true ){
		pl_opt_update( $this->slug, $val );
	}

}

function pl_draft_mode(){
	
	$draft = new EditorDraft;
	
	return ($draft->mode == 'draft') ? true : false;
		
}