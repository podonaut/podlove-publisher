<?php
namespace Podlove\Modules\podtrac;
use \Podlove\Model;

class podtrac extends \Podlove\Modules\Base {

	protected $module_name = 'podtrac';
	protected $module_description = 'Add Podtrac Analytics;
	protected $module_group = 'External Services';
	public function load() {
			add_action( 'init', array( $this, 'register_hooks' ) );
# 			$this->register_option( 'fyyd_verifycode', 'string', array(
# 					'label'       => __( 'fyyd verifycode', 'podlove-podcasting-plugin-for-wordpress' ),
# 					'description' => __( 'Code to verify your ownership at fyyd', 'podlove-podcasting-plugin-for-wordpress' ),
# 					'html'        => array(
# 							'class' => 'regular-text podlove-check-input',
# 							'data-podlove-input-type' => 'text',
# 							'placeholder' => 'yourverifycodehere'
# 					)
# 			) );
	}


	public function register_hooks() {
# 			$fyyd_verifycode = $this->get_module_option( 'fyyd_verifycode' );
# 			if ( ! $fyyd_verifycode )
# 					return;
# 			add_action( 'podlove_rss2_head', function( $feed ) use ( $fyyd_verifycode ) {
# 					echo "\n\t" . sprintf( '<fyyd:verify xmlns:fyyd="https://fyyd.de/fyyd-ns/">%s</fyyd:verify>'."\n\t", $fyyd_verifycode );
# 			} );

	}

}
