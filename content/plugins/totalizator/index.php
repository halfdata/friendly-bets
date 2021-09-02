<?php
if (!defined('INTEGRITY')) exit;
define('T_VERSION', 2.00);

register_activation_hook(array("t_class", "install"));

class t_class {
	var $campaign_details = null;
	var $gametypes = array();
	var $default_campaign_options = array();

	function __construct() {
		global $options, $language;
		load_translation('t', $language, dirname(__FILE__).'/languages/');
		$this->gametypes = array(
			'group-a' => esc_html__('Group A', 't'),
			'group-b' => esc_html__('Group B', 't'),
			'group-c' => esc_html__('Group C', 't'),
			'group-d' => esc_html__('Group D', 't'),
			'group-e' => esc_html__('Group E', 't'),
			'group-f' => esc_html__('Group F', 't'),
			'group-g' => esc_html__('Group G', 't'),
			'group-h' => esc_html__('Group H', 't'),
			'one-sixteenth-final' => esc_html__('1/16 Final', 't'),
			'one-eighth-final' => esc_html__('1/8 Final', 't'),
			'one-fourth-final' => esc_html__('1/4 Final', 't'),
			'semi-final' => esc_html__('Semi-final', 't'),
			'bronze' => esc_html__('Bronze game', 't'),
			'final' => esc_html__('Final', 't')
		);
		$this->default_campaign_options = array(
			'guessing-enable' => 'off',
			'guessing-disclosure-date' => time()
		);
		
		if (is_admin()) {
			add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_action('admin_head', array(&$this, 'admin_head'));
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('ajax-totalizator-campaign-delete', array(&$this, "campaign_delete"));
			add_action('ajax-totalizator-campaign-status-toggle', array(&$this, "campaign_status_toggle"));
			add_action('ajax-totalizator-campaign-save', array(&$this, "campaign_save"));
			add_action('ajax-totalizator-campaign-join', array(&$this, "campaign_join"));
			add_action('ajax-totalizator-campaign-quit', array(&$this, "campaign_quit"));
			add_action('ajax-totalizator-game-save', array(&$this, "game_save"));
			add_action('ajax-totalizator-game-delete', array(&$this, "game_delete"));
			add_action('ajax-totalizator-participant-delete', array(&$this, "participant_delete"));
			add_action('ajax-totalizator-bet-set', array(&$this, "bet_set"));
			add_action('ajax-totalizator-guessing-set', array(&$this, "guessing_set"));
			add_action('ajax-totalizator-guessing-participants-save', array(&$this, "guessing_participants_save"));
		} else {
		}
	}

	function admin_enqueue_scripts() {
		enqueue_script("jquery");
		enqueue_style('t-css', plugins_url('/css/style.css', __FILE__), array(), T_VERSION);
		enqueue_script('t-js', plugins_url('/js/script.js', __FILE__), array(), T_VERSION);
	}

	static function install () {
		global $wpdb;
		$version = get_option('t-version', 0);
		if ($version < T_VERSION) {
			$table_name = $wpdb->prefix."t_bets";
			if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
				$sql = "CREATE TABLE ".$table_name." (
					id int(11) NOT NULL AUTO_INCREMENT,
					game_id int(11) DEFAULT NULL,
					participant_id int(11) DEFAULT NULL,
					team1_points int(11) DEFAULT NULL,
					team2_points int(11) DEFAULT NULL,
					penalty int(11) DEFAULT '0',
					deleted int(11) DEFAULT '0',
					created int(11) DEFAULT NULL,
					UNIQUE KEY  id (id)
				);";
				$wpdb->query($sql);
			}
			$table_name = $wpdb->prefix."t_campaigns";
			if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
				$sql = "CREATE TABLE ".$table_name." (
					id int(11) NOT NULL AUTO_INCREMENT,
					uuid varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
					title longtext COLLATE utf8_unicode_ci,
					description longtext COLLATE utf8_unicode_ci,
					begin_date bigint(20) DEFAULT NULL,
					end_date bigint(20) DEFAULT NULL,
					nobet_penalty int(11) DEFAULT NULL,
					logo int(11) DEFAULT '0',
					type varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
					status varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
					owner_id int(11) DEFAULT NULL,
					options longtext COLLATE utf8_unicode_ci,
					deleted int(11) DEFAULT NULL,
					created int(11) DEFAULT NULL,
					UNIQUE KEY  id (id)
				);";
				$wpdb->query($sql);
			}
			$table_name = $wpdb->prefix."t_games";
			if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
				$sql = "CREATE TABLE ".$table_name." (
					id int(11) NOT NULL AUTO_INCREMENT,
					uuid varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
					campaign_id int(11) DEFAULT NULL,
					gametype varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
					team1_slug varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
					team2_slug varchar(31) COLLATE utf8_unicode_ci DEFAULT NULL,
					team1_points int(11) DEFAULT NULL,
					team2_points int(11) DEFAULT NULL,
					begin_time int(11) DEFAULT NULL,
					deleted int(11) DEFAULT NULL,
					created int(11) DEFAULT NULL,
					UNIQUE KEY id (id)
				);";
				$wpdb->query($sql);
			}
			$table_name = $wpdb->prefix."t_guessing";
			if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
				$sql = "CREATE TABLE ".$table_name." (
					id int(11) NOT NULL AUTO_INCREMENT,
					participant_id int(11) DEFAULT NULL,
					p1_id int(11) DEFAULT NULL,
					p2_id int(11) DEFAULT NULL,
					UNIQUE KEY  id (id)
				);";
				$wpdb->query($sql);
			}
			$table_name = $wpdb->prefix."t_participants";
			if($wpdb->get_var("SHOW TABLES LIKE '".$table_name."'") != $table_name) {
				$sql = "CREATE TABLE ".$table_name." (
					id int(11) NOT NULL AUTO_INCREMENT,
					uuid varchar(63) COLLATE utf8_unicode_ci DEFAULT NULL,
					campaign_id int(11) DEFAULT NULL,
					user_id int(11) DEFAULT NULL,
					nickname varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
					guessing int(11) DEFAULT '1',
					deleted int(11) DEFAULT NULL,
					created int(11) DEFAULT NULL,
					UNIQUE KEY  id (id)
				);";
				$wpdb->query($sql);
			}
			save_option('t-version', T_VERSION);
		}
	}


	function admin_head() {
		global $wpdb;
	}

	function admin_init() {
		global $wpdb, $options, $user_details;
		$this->campaign_details = null;
		if (array_key_exists('cid', $_REQUEST)) {
			$campaign_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_REQUEST['cid']);
			$this->campaign_details = $wpdb->get_row("SELECT t1.*, t2.filename AS logo_filename, t3.uuid AS logo_user_uid, t4.id AS participant_id, t4.nickname AS participant_nickname, t4.guessing AS participant_guessing FROM ".$wpdb->prefix."t_campaigns t1 
					LEFT JOIN ".$wpdb->prefix."uploads t2 ON t2.id = t1.logo AND t2.deleted != '1' AND t2.status = 'active'
					LEFT JOIN ".$wpdb->prefix."users t3 ON t3.id = t2.user_id
					LEFT JOIN ".$wpdb->prefix."t_participants t4 ON t4.campaign_id = t1.id AND t4.deleted != '1'".(!empty($user_details) ? " AND t4.user_id = '".esc_sql($user_details['id'])."'" : " AND t4.user_id = '0'")."
				WHERE t1.uuid = '".esc_sql($campaign_id)."' AND t1.deleted != '1'".(empty($user_details) || $user_details['role'] != 'admin' ? " AND (t1.status = 'active'".(!empty($user_details) ? " OR t1.owner_id = '".esc_sql($user_details['id'])."'" : "").")" : ""), ARRAY_A);
			if (!empty($this->campaign_details)) {
				$this->campaign_details['options'] = json_decode($this->campaign_details['options'], true);
				if (!empty($this->campaign_details['options'])) $this->campaign_details['options'] = array_merge($this->default_campaign_options, $this->campaign_details['options']);
				else $this->campaign_details['options'] = $this->default_campaign_options;
			}
		}
		//if (!empty($this->campaign_details)) {
		add_action('admin_menu_bottom', array(&$this, 'campaign_bar'));
		//}
	}
	function campaign_bar() {
		global $wpdb, $options, $user_details, $page, $languages, $language;
		if ($page['slug'] == 'index') {
			$upload_dir = upload_dir();
			$image = null;
			if ($options['pattern'] > 0) {
				$image = $wpdb->get_row("SELECT t1.*, t2.uuid AS user_uid FROM ".$wpdb->prefix."uploads t1 
						JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
					WHERE t1.id = '".esc_sql($options['pattern'])."' AND t1.deleted != '1'", ARRAY_A);
			}
			$title = translatable_parse($options['title']);
			$tagline = translatable_parse($options['tagline']);
			echo '
			<div class="header-menu-bottom totalizator-index-header" style="background-image: url('.(!empty($image) ? esc_html($upload_dir['baseurl'].'/'.$image['user_uid'].'/'.$image['filename']) : url('').'images/default-pattern.png').');">
				<div class="header-menu-bottom-container">
					<h1>'.(array_key_exists($language, $title) && !empty($title[$language]) ? esc_html($title[$language]) : esc_html($title['default'])).'</h1>
					'.(!empty($tagline['default']) ? '<h2>'.(array_key_exists($language, $tagline) && !empty($tagline[$language]) ? esc_html($tagline[$language]) : esc_html($tagline['default'])).'</h2>' : '').'
				</div>
			</div>';
		} else if (!empty($this->campaign_details)) {
			$upload_dir = upload_dir();
			$subheader = '';
			$button = '';
			$campaign_title = translatable_parse($this->campaign_details['title']);
			if (!empty($this->campaign_details['participant_id'])) {
				$nickname = esc_html($this->campaign_details['participant_nickname']);
				if ($this->campaign_details['begin_date'] > time()) {
					$subheader = '<h2>'.sprintf(esc_html__('Your nickname: %s', 't'), '<a href="'.esc_html(url('?page=add-participant&cid='.$this->campaign_details['uuid'])).'">'.$nickname.'</a>').'</h2>';
					$button = '
					<a class="button2 button-red" href="#" onclick="return totalizator_campaign_quit(this);" data-id="'.esc_html($this->campaign_details['uuid']).'" data-doing="'.esc_html__('Loading...').'">
						<span>'.esc_html__('Quit Totalizator', 't').'</span>
						<i class="fas fa-sign-out-alt"></i>
					</a>';
				} else $subheader = '<h2>'.sprintf(esc_html__('Your nickname: %s', 't'), $nickname).'</h2>';
			} else {
				if ($this->campaign_details['end_date'] > time()) $button = '<a class="button2" href="'.esc_html(url('?page=add-participant&cid='.$this->campaign_details['uuid'])).'">'.esc_html__('Join Totalizator', 't').'</a>';
			}
			echo '
			<div class="header-menu-bottom totalizator-header-menu-bottom">
				<div class="header-menu-bottom-container">
					<div class="totalizator-campaign-bar">
						<div class="totalizator-campaign-bar-logo">
							<img src="'.(!empty($this->campaign_details['logo_filename']) ? esc_html($upload_dir['baseurl'].'/'.$this->campaign_details['logo_user_uid'].'/'.$this->campaign_details['logo_filename']) : plugins_url('/images/default-logo.png', __FILE__)).'" alt="'.esc_html($this->campaign_details['title']).'" />
						</div>
						<div class="totalizator-campaign-bar-content">
							<h1>'.(array_key_exists($language, $campaign_title) && !empty($campaign_title[$language]) ? esc_html($campaign_title[$language]) : esc_html($campaign_title['default'])).'</h1>
							'.$subheader.'
						</div>
						<div class="totalizator-campaign-bar-button">
							'.$button.'
						</div>
					</div>
				</div>
			</div>';
		}
	}
	function admin_menu() {
		global $user_details;
		add_menu_page(
			esc_html__('Totalizators', 't')
			, "index"
			, array()
			, ''
			, array(&$this, 'page_campaigns')
			, array()
			, 'none'
		);
		add_menu_page(
			''
			, "add-campaign"
			, array()
			, 'user'
			, array(&$this, 'page_create_campaign')
			, array()
			, 'none'
		);
		add_menu_page(
			''
			, "add-participant"
			, array()
			, 'user'
			, array(&$this, 'page_join_campaign')
			, array()
			, 'none'
		);
		if (!empty($this->campaign_details)) {
			add_menu_page(
				esc_html__('Participants', 't')
				, "participants"
				, array('cid' => $this->campaign_details['uuid'])
				, ''
				, array(&$this, 'page_participants')
				, array()
				, 'none'
			);
			add_menu_page(
				''
				, "participant-bets"
				, array('cid' => $this->campaign_details['uuid'])
				, ''
				, array(&$this, 'page_participant_bets')
				, array()
				, 'none'
			);
			add_menu_page(
				esc_html__('Games', 't')
				, "games"
				, array('cid' => $this->campaign_details['uuid'])
				, ''
				, array(&$this, 'page_games')
				, array()
				, 'none'
			);
			add_menu_page(
				''
				, "add-game"
				, array('cid' => $this->campaign_details['uuid'])
				, 'user'
				, array(&$this, 'page_create_game')
				, array()
				, 'none'
			);
			add_menu_page(
				''
				, "game-bets"
				, array('cid' => $this->campaign_details['uuid'])
				, ''
				, array(&$this, 'page_game_bets')
				, array()
				, 'none'
			);
			if (!empty($this->campaign_details['participant_id'])) {
				add_menu_page(
					esc_html__('My Bets', 't')
					, "my-bets"
					, array('cid' => $this->campaign_details['uuid'])
					, 'user'
					, array(&$this, 'page_my_bets')
					, array()
					, 'none'
				);
				add_menu_page(
					''
					, "add-bet"
					, array('cid' => $this->campaign_details['uuid'])
					, 'user'
					, array(&$this, 'page_set_bet')
					, array()
					, 'none'
				);
			}
			if ($this->campaign_details['options']['guessing-enable'] == 'on' && $this->campaign_details['begin_date'] < time()) {
				if (!empty($user_details) && ($this->campaign_details['owner_id'] == $user_details['id'] || $user_details['role'] == 'admin')) {
					add_menu_page(
						esc_html__('Guessing', 't')
						, "guessing-menu"
						, array()
						, 'user'
						, ''
						, array()
						, 'none'
					);
					add_submenu_page(
						'guessing-menu'
						, esc_html__('Participants', 't')
						, 'guessing-participants'
						, array('cid' => $this->campaign_details['uuid'])
						, 'user'
						, array(&$this, 'page_guessing_participants')
					);
					if (!empty($this->campaign_details['participant_id']) && $this->campaign_details['participant_guessing'] == 1 && $this->campaign_details['end_date'] > time()) {
						add_submenu_page(
							'guessing-menu'
							, esc_html__('My Guessing', 't')
							, 'set-guessing'
							, array('cid' => $this->campaign_details['uuid'])
							, 'user'
							, array(&$this, 'page_set_guessing')
						);
						if ($this->campaign_details['options']['guessing-disclosure-date'] > time()) $result_options = array('a-attr' => 'onclick="return totalizator_guessing_results(this);"');
						else $result_options = array();
					} else $result_options = array();
					add_submenu_page(
						'guessing-menu'
						, esc_html__('Results', 't')
						, 'guessing'
						, array('cid' => $this->campaign_details['uuid'])
						, 'user'
						, array(&$this, 'page_guessing')
						, $result_options
					);
					add_menu_page(
						''
						, "guessing-details"
						, array('cid' => $this->campaign_details['uuid'])
						, 'user'
						, array(&$this, 'page_guessing_details')
						, array()
						, 'none'
					);
				} else if (!empty($this->campaign_details['participant_id']) && $this->campaign_details['participant_guessing'] == 1) {
					add_menu_page(
						esc_html__('Guessing', 't')
						, "guessing"
						, array('cid' => $this->campaign_details['uuid'])
						, 'user'
						, array(&$this, 'page_guessing')
						, array()
						, 'none'
					);
					add_menu_page(
						''
						, "guessing-details"
						, array('cid' => $this->campaign_details['uuid'])
						, 'user'
						, array(&$this, 'page_guessing_details')
						, array()
						, 'none'
					);
					add_menu_page(
						''
						, "set-guessing"
						, array('cid' => $this->campaign_details['uuid'])
						, 'user'
						, array(&$this, 'page_set_guessing')
						, array()
						, 'none'
					);
				}
			}
		}
		add_menu_page(
			esc_html__('FAQ', 't')
			, "faq"
			, array()
			, ''
			, array(&$this, 'page_faq')
			, array()
			, 'none'
		);
	}

	function page_campaigns() {
		global $wpdb, $options, $user_details, $language;
		$content = '';
		if (empty($user_details)) {
			$additinal_filter = " AND t1.type = 'public' AND t1.status = 'active'";
	 	} else if ($user_details['role'] != 'admin') {
			$additinal_filter = " AND ((t1.type = 'public' AND t1.status = 'active') OR t1.owner_id = '".esc_sql($user_details['id'])."' OR t6.id IS NOT NULL)";
		} else $additinal_filter = "";

		$tmp = $wpdb->get_row("SELECT COUNT(*) AS total FROM ".$wpdb->prefix."t_campaigns t1 LEFT JOIN ".$wpdb->prefix."t_participants t6 ON t1.id = t6.campaign_id AND t6.deleted != '1'".(!empty($user_details) ? " AND t6.user_id = '".esc_sql($user_details['id'])."'" : " AND t6.user_id = '0'")." WHERE t1.deleted != '1'".$additinal_filter, ARRAY_A);
		$total = $tmp["total"];
		$totalpages = ceil($total/RECORDS_PER_PAGE);
		if ($totalpages == 0) $totalpages = 1;
		if (array_key_exists('p', $_GET)) $page = intval($_GET["p"]);
		else $page = 1;
		if ($page < 1 || $page > $totalpages) $page = 1;
		$switcher = page_switcher($options['url'], $page, $totalpages);

		$sql = "SELECT t1.*, t2.games, t3.passed_games, t4.participants, t5.uuid AS user_uuid, t5.name AS user_name, t5.email AS user_email, t5.deleted AS user_deleted, t6.id AS participant_id, t6.nickname AS participant_nickname FROM ".$wpdb->prefix."t_campaigns t1
			LEFT JOIN (SELECT campaign_id, COUNT(id) AS games FROM ".$wpdb->prefix."t_games WHERE deleted != '1' GROUP BY campaign_id) t2 ON t1.id = t2.campaign_id
			LEFT JOIN (SELECT campaign_id, COUNT(id) AS passed_games FROM ".$wpdb->prefix."t_games WHERE deleted != '1' AND begin_time < '".time()."' GROUP BY campaign_id) t3 ON t1.id = t3.campaign_id
			LEFT JOIN (SELECT campaign_id, COUNT(id) AS participants FROM ".$wpdb->prefix."t_participants WHERE deleted != '1' GROUP BY campaign_id) t4 ON t1.id = t4.campaign_id
			LEFT JOIN ".$wpdb->prefix."users t5 ON t5.id = t1.owner_id
			LEFT JOIN ".$wpdb->prefix."t_participants t6 ON t1.id = t6.campaign_id AND t6.deleted != '1'".(!empty($user_details) ? " AND t6.user_id = '".esc_sql($user_details['id'])."'" : " AND t6.user_id = '0'")."
			WHERE t1.deleted != '1'".$additinal_filter."
			ORDER BY begin_date DESC LIMIT ".(($page-1)*RECORDS_PER_PAGE).", ".RECORDS_PER_PAGE;
		$rows = $wpdb->get_results($sql, ARRAY_A);

		$upload_dir = upload_dir();

		$title = esc_html__('Totalizators', 't');
		if (sizeof($rows) > 0) {
			$content .= '
		<div class="table-funcbar">
			<div class="table-pageswitcher">'.$switcher.'</div>
			<div class="table-buttons"><a href="'.esc_html(url('?page=add-campaign')).'" class="button button-small"><i class="fas fa-plus"></i><span>'.esc_html__('Create New Totalizator', 't').'</span></a></div>
		</div>';

			foreach ($rows as $row) {
				$logo = $wpdb->get_row("SELECT t1.*, t2.uuid AS user_uid FROM ".$wpdb->prefix."uploads t1 
					JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
					WHERE t1.id = '".esc_sql($row['logo'])."' AND t1.deleted != '1'", ARRAY_A);
				$campaign_title	= translatable_parse($row['title']);
				$campaign_description	= translatable_parse($row['description']);
				$content .= '
			<div class="totalizator-panel'.($row['end_date'] < time() ? ' totalizator-panel-finished' : '').'">
				<a href="'.esc_html(url('?page=games&cid='.urlencode($row['uuid']))).'"></a>
				'.(!empty($user_details) && ($user_details['role'] == 'admin' || $row['owner_id'] == $user_details['id']) ?
				'<div class="totalizator-panel-actions">
					<div class="item-menu">
						<span><i class="fas fa-ellipsis-v"></i></span>
						<ul>
							<li><a href="'.url('?page=add-campaign&id='.esc_html($row['uuid'])).'">'.esc_html__('Edit', 't').'</a></li>
							'.(in_array($row['status'], array('active', 'inactive')) ? '<li><a href="#" data-status="'.esc_html($row['status']).'" data-id="'.esc_html($row['uuid']).'" data-doing="'.($row['status'] == 'active' ? esc_html__('Deactivating...', 't') : esc_html__('Activating...', 't')).'" onclick="return totalizator_campaign_status_toggle(this);">'.($row['status'] == 'active' ? esc_html__('Deactivate', 't') : esc_html__('Activate', 't')).'</a></li>' : '').'
							<li class="item-menu-line"></li>
							<li><a href="#" data-id="'.esc_html($row['uuid']).'" data-doing="'.esc_html__('Deleting...', 't').'" onclick="return totalizator_campaign_delete(this);">'.esc_html__('Delete', 't').'</a></li>
						</ul>
					</div>
				</div>' : '').'
				<span class="totalizator-panel-badges">
					'.($row['end_date'] < time() ? '<span class="totalizator-panel-badge totalizator-panel-badge-danger">'.esc_html__('Finished', 't').'</span>' : '').'
					<span class="totalizator-badge-status">'.($row['status'] == 'inactive' ? '<span class="totalizator-panel-badge totalizator-panel-badge-danger">'.esc_html__('Inactive', 't').'</span>' : '').'</span>
					'.(!empty($user_details) && $row['owner_id'] == $user_details['id'] ? '<span class="totalizator-panel-badge totalizator-panel-badge-info">'.esc_html__('Owner', 't').'</span>' : '').'
					'.($row['type'] == 'private' ? '<span class="totalizator-panel-badge totalizator-panel-badge-success">'.esc_html__('Private', 't').'</span>' : '').'
				</span>
				<div class="totaliator-panel-logo">
					<img class="totaliator-panel-logo-img" src="'.(!empty($logo) ? esc_html($upload_dir['baseurl'].'/'.$logo['user_uid'].'/'.$logo['filename']) : plugins_url('/images/default-logo.png', __FILE__)).'" alt="'.esc_html($row['title']).'" />
				</div>
				<div class="totaliator-panel-content">
					<h3>'.(array_key_exists($language, $campaign_title) && !empty($campaign_title[$language]) ? esc_html($campaign_title[$language]) : esc_html($campaign_title['default'])).'</h3>
					<div class="totalizator-panel-description">'.(array_key_exists($language, $campaign_description) && !empty($campaign_description[$language]) ? esc_html($campaign_description[$language]) : esc_html($campaign_description['default'])).'</div>
					'.esc_html__('Details', 't').':
					<ul>
						<li>'.esc_html__('Period', 't').': '.timestamp_string($row['begin_date'], $options['date-format']).' - '.timestamp_string($row['end_date'], $options['date-format']).'</li>
						<li>'.esc_html__('Missed bet penalty', 't').': '.esc_html($row['nobet_penalty']).' '.esc_html__('points', 't').'</li>
						<li>'.esc_html__('Participants', 't').': '.intval($row['participants']).'</li>
						<li>'.esc_html__('Passed games', 't').': '.intval($row['passed_games']).'</li>
						'.(!empty($user_details) && $user_details['role'] == 'admin' ? '<li>'.esc_html__('Owner', 't').': '.esc_html($row['user_name'].' ('.$row['user_email'].')').'</li>' : '').'
					</ul>
					'.(((empty($row['participant_id']) || $row['type'] == 'private') && $row['end_date'] >= time()) ? 
					'<div class="totalizator-panel-join">
						'.($row['type'] == 'public' ?
						'<a class="button2" href="'.esc_html(url('?page=add-participant&cid='.$row['uuid'])).'">'.esc_html__('Join Totalizator', 't').'</a>' : 
						'<label>'.esc_html__('Join link', 't').':</label>
						<div class="totalizator-join-link-container">
							<input readonly="readonly" type="text" value="'.esc_html(url('?page=add-participant&cid='.$row['uuid'])).'" onclick="this.focus();this.select();">
							<span class="tooltipster" title="'.esc_html__('Copy to clipboard', 't').'" onclick="jQuery(this).parent().find(\'input\').select();document.execCommand(\'copy\');"><i class="far fa-copy"></i></span>
						</div>').'
					</div>' : '').'
				</div>
			</div>';
			}
			$content .= '
			<div class="table-funcbar">
				<div class="table-pageswitcher">'.$switcher.'</div>
				<div class="table-buttons"><a href="'.esc_html(url('?page=add-campaign')).'" class="button button-small"><i class="fas fa-plus"></i><span>'.esc_html__('Create New Totalizator', 't').'</span></a></div>
			</div>';
		} else {
			$content .= '
			<div class="totalizator-nocampaigns">
				<div class="totalizator-nocampaigns-box">
					<p>'.esc_html__('Welcome. There are no available totalizators yet.', 't').'</p>
					<a href="'.esc_html(url('?page=add-campaign')).'" class="button2"><i class="fas fa-plus"></i><span>'.esc_html__('Create First Totalizator', 't').'</span></a>
				</div>
			</div>';
		}
		return array('title' => $title, 'content' => $content);
	}

	function campaign_delete() {
		global $wpdb, $options, $user_details;
		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if (array_key_exists('campaign-id', $_REQUEST)) {
			$campaign_uid = preg_replace('/[^a-zA-Z0-9-]/', '', trim(stripslashes($_REQUEST['campaign-id'])));
			$campaign = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."t_campaigns WHERE uuid = '".esc_sql($campaign_uid)."' AND deleted != '1'".($user_details['role'] != 'admin' ? " AND owner_id = '".esc_sql($user_details['id'])."'" : ""), ARRAY_A);
			if (empty($campaign)) {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 't'));
				echo json_encode($return_data);
				exit;
			}
		} else {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}

		$wpdb->query("UPDATE ".$wpdb->prefix."t_campaigns SET deleted = '1' WHERE id = '".esc_sql($campaign['id'])."'");
	
		$_SESSION['success-message'] = esc_html__('Totalizator successfully deleted.', 't');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Totalizator successfully deleted.', 't'));
		echo json_encode($return_object);
		exit;
	
	}

	function campaign_status_toggle() {
		global $wpdb, $options, $user_details;
		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if (array_key_exists('campaign-id', $_REQUEST)) {
			$campaign_uid = preg_replace('/[^a-zA-Z0-9-]/', '', trim(stripslashes($_REQUEST['campaign-id'])));
			$status = trim(stripslashes($_REQUEST['status']));
			$campaign = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."t_campaigns WHERE uuid = '".esc_sql($campaign_uid)."' AND deleted != '1'".($user_details['role'] != 'admin' ? " AND owner_id = '".esc_sql($user_details['id'])."'" : ""), ARRAY_A);
			if (empty($campaign)) {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 't'));
				echo json_encode($return_data);
				exit;
			}
		} else {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}

		if ($status == 'active') {
			$wpdb->query("UPDATE ".$wpdb->prefix."t_campaigns SET status = 'inactive' WHERE id = '".esc_sql($campaign['id'])."'");
			$return_object = array(
				'status' => 'OK',
				'message' => esc_html__('Totalizator successfully deactivated.', 't'),
				'campaign_action' => esc_html__('Activate', 't'),
				'campaign_action_doing' => esc_html__('Activating...', 't'),
				'campaign_status' => 'inactive',
				'campaign_status_label' => esc_html__('Inactive', 't')
			);
		} else {
			$wpdb->query("UPDATE ".$wpdb->prefix."t_campaigns SET status = 'active' WHERE id = '".esc_sql($campaign['id'])."'");
			$return_object = array(
				'status' => 'OK',
				'message' => esc_html__('Totalizator successfully activated.', 't'),
				'campaign_action' => esc_html__('Deactivate', 't'),
				'campaign_action_doing' => esc_html__('Deactivating...', 't'),
				'campaign_status' => 'active',
				'campaign_status_label' => esc_html__('Active', 't')
			);
		}
		echo json_encode($return_object);
		exit;
	}

	function page_create_campaign() {
		global $wpdb, $options, $user_details;

		$content = '';
		if (empty($user_details)) {
			header("Location: ".url(''));
			exit;
		}
		$campaign = null;
		if (array_key_exists('id', $_GET)) {
			$campaign_uid = preg_replace('/[^a-zA-Z0-9-]/', '', trim(stripslashes($_GET['id'])));
			$campaign = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."t_campaigns WHERE uuid = '".esc_sql($campaign_uid)."' AND deleted != '1'".($user_details['role'] != 'admin' ? " AND owner_id = '".esc_sql($user_details['id'])."'" : ""), ARRAY_A);
		}

		if (!empty($campaign)) {
			$campaign_options = json_decode($campaign['options'], true);
			if (!empty($campaign_options)) $campaign_options = array_merge($this->default_campaign_options, $campaign_options);
			else $campaign_options = $this->default_campaign_options;
		} else $campaign_options = $this->default_campaign_options;

		$title = empty($campaign) ? esc_html__('Create Totalizator', 't') : esc_html__('Edit Totalizator', 't');
		$content .= '
		<h1>'.(empty($campaign) ? esc_html__('Create Totalizator', 't') : esc_html__('Edit Totalizator', 't')).'</h1>
		<div class="form" id="campaign-form">
		'.($user_details['role'] != 'admin' && (empty($campaign) || $campaign['type'] == 'private') ? '
			<div class="form-row">
				<div class="form-label"></div>
				<div class="form-tooltip"></div>
				<div class="form-content">
					<div class="inline-message inline-message-noclose inline-message-success">
						'.esc_html__('This is a private (hidden) totalizator. It does not appear in the list of totalizators. Users can join it using special link.', 't').'
					</div>
				</div>
			</div>' : '').'
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Title', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Specify totalizator title.', 't').'
					</div>
				</div>
				<div class="form-content">
					'.translatable_input_html('title', (!empty($campaign) ? $campaign['title'] : ''), esc_html__('Title', 'fb')).'
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Description', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Describe totalizator.', 't').'
					</div>
				</div>
				<div class="form-content">
					'.translatable_textarea_html('description', (!empty($campaign) ? $campaign['description'] : ''), esc_html__('Describe totalizator', 'fb')).'
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Image', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Upload image/logo of totalizator.', 't').'
					</div>
				</div>
				<div class="form-content">
					'.image_uploader_html('logo', (!empty($campaign) ? $campaign['logo'] : 0), plugins_url('/images/default-logo.png', __FILE__)).'
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Period', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Set begin and end dates of totalizator.', 't').'
					</div>
				</div>
				<div class="form-content">
					<div class="columns">
						<div class="column column-30">
							<div class="input-box">
								<div class="input-element">
									<input class="errorable date" type="text" name="begin-date" placeholder="'.esc_html__('Begin', 't').'" value="'.(!empty($campaign) ? timestamp_string($campaign['begin_date'], $options['date-format']) : '').'" data-default="'.(!empty($campaign) ? timestamp_string($campaign['begin_date'], 'Y-m-d') : '').'" data-max-type="field" data-max-value="end-date" readonly="readonly">
								</div>
								<label>'.esc_html__('Start', 't').'</label>
							</div>
						</div>
						<div class="column column-30">
							<div class="input-box">
								<div class="input-element">
									<input class="errorable date" type="text" name="end-date" placeholder="'.esc_html__('End', 't').'" value="'.(!empty($campaign) ? timestamp_string($campaign['end_date'], $options['date-format']) : '').'" data-default="'.(!empty($campaign) ? timestamp_string($campaign['end_date'], 'Y-m-d') : '').'" data-min-type="field" data-min-value="begin-date" readonly="readonly">
								</div>
								<label>'.esc_html__('End', 't').'</label>
							</div>
						</div>
						<div class="column column-40"></div>
					</div>
				</div>
			</div>
			'.($user_details['role'] != 'admin' ? '<input type="hidden" name="type" value="private">' : '
            <div class="form-row">
                <div class="form-label">
                    <label>'.esc_html__('Type', 't').'</label>
                </div>
                <div class="form-tooltip">
                    <i class="fas fa-question-circle form-tooltip-anchor"></i>
                    <div class="form-tooltip-content">
                        '.esc_html__('Set type of totalizator. Public totalizator appears in the list and any user can join it. Private totalizator is not visible. User can join it using special link.', 't').'
                    </div>
                </div>
                <div class="form-content">
                    <div class="input-box">
                        <div class="bar-selector">
                            <input class="radio" id="type-private" type="radio" name="type" value="private"'.(empty($campaign) || $campaign['type'] != 'public' ? ' checked="checked"' : '').'><label for="type-private">'.esc_html__('Private', 't').'</label><input class="radio" id="type-public" type="radio" name="type" value="public"'.(empty($campaign) || $campaign['type'] != 'public' ? '' : ' checked="checked"').'><label for="type-public">'.esc_html__('Public', 't').'</label>
                        </div>
                    </div>
                </div>
            </div>').'
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Enable guessing', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Allow users to guess who is hidden behind of participant nickname.', 't').'
					</div>
				</div>
				<div class="form-content">
					<div class="input-box">
						<div class="checkbox-toggle-container">
							<input class="checkbox-toggle" type="checkbox" value="on" id="guessing-enable" name="guessing-enable"'.($campaign_options['guessing-enable'] == 'on' ? ' checked="checked"' : '').' onchange="if(jQuery(this).is(\':checked\')){jQuery(\'#guessing-parameters\').fadeIn(100);}else{jQuery(\'#guessing-parameters\').fadeOut(100);}">
							<label for="guessing-enable"></label>
						</div>
					</div>
				</div>
			</div>
			<div id="guessing-parameters"'.($campaign_options['guessing-enable'] == 'off' ? ' style="display:none;"' : '').'>
				<div class="form-row">
					<div class="form-label">
						<label>'.esc_html__('Disclosure date', 't').'</label>
					</div>
					<div class="form-tooltip">
						<i class="fas fa-question-circle form-tooltip-anchor"></i>
						<div class="form-tooltip-content">
							'.esc_html__('Set guessing disclosure date.', 't').'
						</div>
					</div>
					<div class="form-content">
						<div class="columns">
							<div class="column column-30">
								<div class="input-box">
									<input class="errorable date" type="text" name="guessing-disclosure-date" placeholder="'.esc_html__('Disclosure date', 't').'" value="'.timestamp_string($campaign_options['guessing-disclosure-date'], $options['date-format']).'" data-default="'.timestamp_string($campaign_options['guessing-disclosure-date'], 'Y-m-d').'" readonly="readonly">
								</div>
							</div>
							<div class="column column-70"></div>
						</div>
					</div>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Missing bet penalty', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Set how many penalty points participant get in case of missing bet.', 't').'
					</div>
				</div>
				<div class="form-content">
					<div class="columns">
						<div class="column column-30">
							<div class="input-box">
								<input class="errorable" type="text" name="nobet-penalty" placeholder="'.esc_html__('Missing bet penalty', 't').'" value="'.(!empty($campaign) ? esc_html($campaign['nobet_penalty']) : '26').'">
							</div>
						</div>
						<div class="column column-70"></div>
					</div>
				</div>
			</div>
			<div class="form-row right-align">
				<input type="hidden" name="action" value="totalizator-campaign-save">
				<input type="hidden" name="id" value="'.(!empty($campaign) ? esc_html($campaign['uuid']) : '').'">
				<a class="button" href="#" onclick="return save_form(this);" data-label="'.esc_html__('Save Details', 't').'">
					<span>'.esc_html__('Save Details', 't').'</span>
					<i class="fas fa-angle-right"></i>
				</a>
			</div>
		</div>';
		return array('title' => $title, 'content' => $content);
	}

	function campaign_save() {
		global $wpdb, $options, $user_details, $gmt_offset;
		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}
		$mandatory_fields = array('id', 'title', 'description', 'logo', 'begin-date', 'end-date', 'nobet-penalty', 'type', 'guessing-disclosure-date');
		$fields = array(
			'id' => '',
			'logo' => '',
			'begin-date' => '',
			'end-date' => '',
			'nobet-penalty' => '',
			'type' => '',
			'guessing-enable' => 'off',
			'guessing-disclosure-date' => ''
		);
		$tr_fields = array(
			'title' => '',
			'description' => ''
		);
		foreach (array_merge($fields, $tr_fields) as $key => $value) {
			if (array_key_exists($key, $_REQUEST)) {
				if (array_key_exists($key, $tr_fields)) $tr_fields[$key] = translatable_populate($key);
				else $fields[$key] = trim(stripslashes($_REQUEST[$key]));
			} else {
				if (in_array($key, $mandatory_fields)) {
					$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request: some fields are missing.', 't'));
					echo json_encode($return_data);
					exit;
				}
			}
		}
		$fields['id'] = preg_replace('/[^a-zA-Z0-9-]/', '', $fields['id']);
		$fields['begin-date'] = validate_date($fields['begin-date'], $options['date-format']);
		$fields['end-date'] = validate_date($fields['end-date'], $options['date-format']);
		$fields['guessing-disclosure-date'] = validate_date($fields['guessing-disclosure-date'], $options['date-format']);

		$errors = array();

		$campaign = null;
		if (!empty($fields['id'])) {
			$campaign = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."t_campaigns WHERE uuid = '".esc_sql($fields['id'])."' AND deleted != '1'".($user_details['role'] != 'admin' ? " AND owner_id = '".esc_sql($user_details['id'])."'" : ""), ARRAY_A);
			if (empty($campaign)) {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('You do not have permissions to perform this action.', 't'));
				echo json_encode($return_data);
				exit;
			}
		}
		if (!empty($campaign)) {
			$campaign_options = json_decode($campaign['options'], true);
			if (!empty($campaign_options)) $campaign_options = array_merge($this->default_campaign_options, $campaign_options);
			else $campaign_options = $this->default_campaign_options;
		} else $campaign_options = $this->default_campaign_options;

		if (mb_strlen($tr_fields['title']['default']) < 2) $errors['title[default]'] = esc_html__('Title is too short.', 't');
		else if (mb_strlen($tr_fields['title']['default']) > 127) $errors['title[default]'] = esc_html__('Title is too long.', 't');
		foreach ($tr_fields['title'] as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 0 && mb_strlen($value) < 2) $errors['title['.$key.']'] = esc_html__('The translation is too short.', 'fb');
				else if (mb_strlen($value) > 127) $errors['title['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}

		if (mb_strlen($tr_fields['description']['default']) < 16) $errors['description[default]'] = esc_html__('Description is too short.', 't');
		else if (mb_strlen($tr_fields['description']['default']) > 4096) $errors['description[default]'] = esc_html__('Description is too long.', 't');
		foreach ($tr_fields['description'] as $key => $value) {
			if ($key != 'default') {
				if (mb_strlen($value) > 0 && mb_strlen($value) < 16) $errors['description['.$key.']'] = esc_html__('The translation is too short.', 'fb');
				else if (mb_strlen($value) > 4096) $errors['description['.$key.']'] = esc_html__('The translation is too long.', 'fb');
			}
		}
		
		if (!empty($fields['nobet-penalty']) && !ctype_digit($fields['nobet-penalty'])) $errors['nobet-penalty'] = esc_html__('Must be a not negative integer value.', 't');
		else {
			$fields['nobet-penalty'] = intval($fields['nobet-penalty']);
			if ($fields['nobet-penalty'] > 1000000) $errors['nobet-penalty'] = esc_html__('Value is too big.', 't');
		}
		if ($fields['begin-date'] === false) $errors['begin-date'] = esc_html__('Invalid date.', 't');
		if ($fields['end-date'] === false) $errors['end-date'] = esc_html__('Invalid date.', 't');
		if ($fields['begin-date'] !== false && $fields['end-date'] !== false) {
			if ($fields['begin-date'] > $fields['end-date']) $errors['end-date'] = esc_html__('Invalid date.', 't');
		}

		if ($fields['guessing-enable'] == 'on') {
			if ($fields['guessing-disclosure-date'] === false) $errors['guessing-disclosure-date'] = esc_html__('Invalid date.', 't');
			else if ($fields['end-date'] !== false && $fields['guessing-disclosure-date'] < $fields['end-date']) $errors['guessing-disclosure-date'] = esc_html__('Must be after end date.', 't');
		}

		if (!empty($errors)) {
			$return_data = array('status' => 'ERROR', 'errors' => $errors);
			echo json_encode($return_data);
			exit;
		}

		$campaign_options['guessing-enable'] = $fields['guessing-enable'];
		$campaign_options['guessing-disclosure-date'] = $fields['guessing-disclosure-date']->getTimestamp()-3600*$gmt_offset;

		$logo = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."uploads WHERE uuid = '".esc_sql($fields['logo'])."' AND deleted != '1'", ARRAY_A);
		if (!empty($logo)) $logo_id = $logo['id'];
		else $logo_id = 0;

		if (!empty($campaign)) {
			$wpdb->query("UPDATE ".$wpdb->prefix."t_campaigns SET
				title = '".esc_sql(json_encode($tr_fields['title']))."', 
				description = '".esc_sql(json_encode($tr_fields['description']))."',  
				begin_date = '".esc_sql($fields['begin-date']->getTimestamp()-3600*$gmt_offset)."', 
				end_date = '".esc_sql($fields['end-date']->getTimestamp()-3600*$gmt_offset)."', 
				nobet_penalty = '".esc_sql($fields['nobet-penalty'])."', 
				logo = '".esc_sql($logo_id)."',
				".($user_details['role'] == 'admin' ? "type = '".esc_sql($fields['type'])."'," : "")."
				options = '".esc_sql(json_encode($campaign_options))."'
			WHERE id = '".esc_sql($campaign['id'])."' 
			");
		} else {
			$wpdb->query("INSERT INTO ".$wpdb->prefix."t_campaigns (
                uuid, 
                title, 
                description, 
                begin_date, 
                end_date, 
                nobet_penalty, 
                logo, 
                type, 
                status,
                owner_id,
				options,
                deleted, 
                created
            ) VALUES (
                '".esc_sql(uuid_v4())."',
                '".esc_sql(json_encode($tr_fields['title']))."',
                '".esc_sql(json_encode($tr_fields['description']))."',
                '".esc_sql($fields['begin-date']->getTimestamp()-3600*$gmt_offset)."',
                '".esc_sql($fields['end-date']->getTimestamp()-3600*$gmt_offset)."',
                '".esc_sql($fields['nobet-penalty'])."',
                '".esc_sql($logo_id)."',
                '".($user_details['role'] == 'admin' ? esc_sql($fields['type']) : "private")."',
                'active',
                '".esc_sql($user_details['id'])."',
				'".esc_sql(json_encode($campaign_options))."',
                '0',
                '".time()."'
            )");
		}
		$_SESSION['success-message'] = esc_html__('Totalizator details successfully saved.', 't');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Totalizator details successfully saved.', 't'), 'url' => url(''));
		echo json_encode($return_object);
		exit;
	}

	function page_join_campaign() {
		global $wpdb, $options, $user_details;

		$content = '';
		if (empty($this->campaign_details)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}
		if ($this->campaign_details['end_date'] < time()) {
			$_SESSION['error-message'] = esc_html__('Totalizator already finished.', 't');
			header("Location: ".url(''));
			exit;
		}
		if (!empty($this->campaign_details['participant_id']) && $this->campaign_details['begin_date'] < time()) {
			$_SESSION['error-message'] = esc_html__('You can not change the nickname when totalizator already started.', 't');
			header("Location: ".url(''));
			exit;
		}
		$title = !empty($this->campaign_details['participant_id']) ? esc_html__('Change nickname', 't') : esc_html__('Join Totalizator', 't');		
		$content .= '
		<h1>'.(!empty($this->campaign_details['participant_id']) ? esc_html__('Change nickname', 't') : esc_html__('Join Totalizator', 't')).'</h1>
		<div class="form" id="join-campaign-form">
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Participant nickname', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Enter your participant nickname.', 't').'
					</div>
				</div>
				<div class="form-content">
					<input class="errorable" type="text" name="nickname" placeholder="'.esc_html__('Nickname', 't').'" value="'.(!empty($this->campaign_details['participant_id']) ? esc_html($this->campaign_details['participant_nickname']) : '').'">
				</div>
			</div>
			<div class="form-row right-align">
				<input type="hidden" name="action" value="totalizator-campaign-join">
				<input type="hidden" name="cid" value="'.esc_html($this->campaign_details['uuid']).'">
				<a class="button" href="#" onclick="return save_form(this);" data-label="'.(!empty($this->campaign_details['participant_id']) ? esc_html__('Save Details', 't') : esc_html__('Join Totalizator', 't')).'">
					<span>'.(!empty($this->campaign_details['participant_id']) ? esc_html__('Save Details', 't') : esc_html__('Join Totalizator', 't')).'</span>
					<i class="fas fa-angle-right"></i>
				</a>
			</div>
		</div>';
		return array('title' => $title, 'content' => $content);
	}

	function campaign_join() {
		global $wpdb, $options, $user_details, $gmt_offset;
		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if (empty($this->campaign_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if ($this->campaign_details['end_date'] < time()) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Totalizator already finished.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if (!empty($this->campaign_details['participant_id']) && $this->campaign_details['begin_date'] < time()) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You can not change the nickname when totalizator already started.', 't'));
			echo json_encode($return_data);
			exit;
		}
		$mandatory_fields = array('cid', 'nickname');
		$fields = array(
			'cid' => '',
			'nickname' => ''
		);
		foreach ($mandatory_fields as $key) {
			if (array_key_exists($key, $_REQUEST)) {
				$fields[$key] = trim(stripslashes($_REQUEST[$key]));
			} else {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request: some fields are missing.', 't'));
				echo json_encode($return_data);
				exit;
			}
		}

		$errors = array();

		if (mb_strlen($fields['nickname']) < 2) $errors['nickname'] = esc_html__('Nickname is too short.', 't');
		else if (mb_strlen($fields['nickname']) > 64) $errors['nickname'] = esc_html__('Nickname is too long.', 't');
		else {
			$participant = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."t_participants WHERE campaign_id = '".esc_sql($this->campaign_details['id'])."' AND deleted != '1' AND nickname = '".esc_sql($fields['nickname'])."'".(!empty($this->campaign_details['participant_id']) ? " AND id != '".esc_sql($this->campaign_details['participant_id'])."'" : ""), ARRAY_A);
			if (!empty($participant)) $errors['nickname'] = esc_html__('Nickname is already in use.', 't');
		}


		if (!empty($errors)) {
			$return_data = array('status' => 'ERROR', 'errors' => $errors);
			echo json_encode($return_data);
			exit;
		}

		if (!empty($this->campaign_details['participant_id'])) {
			$wpdb->query("UPDATE ".$wpdb->prefix."t_participants SET
				nickname = '".esc_sql($fields['nickname'])."'
			WHERE id = '".esc_sql($this->campaign_details['participant_id'])."' 
			");
			$_SESSION['success-message'] = esc_html__('Details are successfully saved.', 't');
			$return_object = array('status' => 'OK', 'message' => esc_html__('Details are successfully saved.', 't'), 'url' => url('?page=games&cid='.$this->campaign_details['uuid']));
		} else {
			$wpdb->query("INSERT INTO ".$wpdb->prefix."t_participants (
				uuid,
                campaign_id, 
                user_id, 
                nickname,
				guessing,
                deleted, 
                created
            ) VALUES (
				'".esc_sql(uuid_v4())."',
				'".esc_sql($this->campaign_details['id'])."',
                '".esc_sql($user_details['id'])."',
                '".esc_sql($fields['nickname'])."',
				'1',
                '0',
                '".time()."'
            )");
			$particpant_id = $wpdb->insert_id;
			$this->update_participant_penalties($this->campaign_details['id'], $particpant_id, $this->campaign_details['nobet_penalty']);
			$_SESSION['success-message'] = esc_html__('You are successfully joined totalizator.', 't');
			$return_object = array('status' => 'OK', 'message' => esc_html__('You are successfully joined totalizator.', 't'), 'url' => url('?page=games&cid='.$this->campaign_details['uuid']));
		}
		echo json_encode($return_object);
		exit;
	}

	function campaign_quit() {
		global $wpdb, $options, $user_details, $gmt_offset;
		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if (empty($this->campaign_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if (empty($this->campaign_details['participant_id'])) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You are not a participant of this totalizator.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if ($this->campaign_details['begin_date'] < time()) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Totalizator already started.', 't'));
			echo json_encode($return_data);
			exit;
		}
		$wpdb->query("UPDATE ".$wpdb->prefix."t_participants SET deleted = '1' WHERE id = '".esc_sql($this->campaign_details['participant_id'])."'");
		$_SESSION['success-message'] = esc_html__('You are no longer a participant of this totalizator.', 't');
		$return_object = array('status' => 'OK', 'message' => esc_html__('You are no longer a participant of this totalizator.', 't'));
		echo json_encode($return_object);
		exit;
	}

	function update_participant_penalties($_campaign_id, $_participant_id, $_nobet_penalty = 25) {
		global $wpdb, $options, $user_details;
		$games = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."t_games WHERE deleted != '1' AND campaign_id = '".esc_sql($_campaign_id)."'", ARRAY_A);
		foreach ($games as $game) {
			$this->update_penalty($game["id"], $_participant_id, $_nobet_penalty);
		}
	}

	function update_game_penalties($_campaign_id, $_game_id, $_nobet_penalty = 25) {
		global $wpdb, $options, $user_details;
		$participants = $wpdb->get_results("SELECT t1.* FROM ".$wpdb->prefix."t_participants t1 
				LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
			WHERE t1.deleted != '1' AND t1.campaign_id = '".esc_sql($_campaign_id)."' AND t2.deleted != '1'", ARRAY_A);
		foreach ($participants as $participant) {
			$this->update_penalty($_game_id, $participant['id'], $_nobet_penalty);
		}
	}

	function update_penalty($_game_id, $_participant_id, $_nobet_penalty = 25) {
		global $wpdb, $options, $user_details;

		$sql = "SELECT t1.*, t2.id AS bet_id, t2.team1_points AS bet_team1_points, t2.team2_points AS bet_team2_points FROM ".$wpdb->prefix."t_games t1
				LEFT JOIN ".$wpdb->prefix."t_bets t2 ON t1.id = t2.game_id AND t2.deleted != '1' AND t2.participant_id = '".esc_sql($_participant_id)."'
			WHERE t1.id = '".esc_sql($_game_id)."' AND t1.deleted != '1'";
		$data = $wpdb->get_row($sql, ARRAY_A);
		if (!empty($data)) {
			$penalty = $this->calc_penalty($data["bet_team1_points"], $data["bet_team2_points"], $data["team1_points"], $data["team2_points"], $_nobet_penalty);
			if (empty($data["bet_id"])) {
				$wpdb->query("INSERT INTO ".$wpdb->prefix."t_bets (
					game_id, 
					participant_id, 
					team1_points,
					team2_points, 
					penalty,
					deleted,
					created
				) VALUES (
					'".esc_sql($_game_id)."',
					'".esc_sql($_participant_id)."',
					'-1',
					'-1',
					'".intval($penalty)."',
					'0',
					'".time()."'
				)");
			} else {
				$wpdb->query("UPDATE ".$wpdb->prefix."t_bets SET penalty = '".intval($penalty)."' WHERE id = '".esc_sql($data["bet_id"])."'");
			}
		}
	}

	function calc_penalty($_bet1, $_bet2, $_points1, $_points2, $_nobet_penalty = 25) {
		if (!is_numeric($_points1) || !is_numeric($_points2)) return 0;
		if ($_points1 < 0 || $_points2 < 0) return 0;
		if (!is_numeric($_bet1) || !is_numeric($_bet2)) return $_nobet_penalty;
		if ($_bet1 < 0 || $_bet2 < 0) return $_nobet_penalty;
		$penalty = 0;
		if ($_points1 == $_points2) {
			if ($_bet1 != $_bet2) $penalty = 20 + abs($_points1-$_bet1) + abs($_points2-$_bet2);
			else if ($_bet1 == $_bet2 && $_bet1 != $_points1) $penalty = 10 + abs($_points1-$_bet1) + abs($_points2-$_bet2);
			else $penalty = 0;
		} else if ($_points1 > $_points2) {
			if ($_bet1 <= $_bet2) $penalty = 20 + abs($_points1-$_bet1) + abs($_points2-$_bet2);
			else if ($_bet1 != $_points1 || $_bet2 != $_points2) $penalty = 10 + abs($_points1-$_bet1) + abs($_points2-$_bet2);
			else $penalty = 0;
		} else if ($_points1 < $_points2) {
			if ($_bet1 >= $_bet2) $penalty = 20 + abs($_points1-$_bet1) + abs($_points2-$_bet2);
			else if ($_bet1 != $_points1 || $_bet2 != $_points2) $penalty = 10 + abs($_points1-$_bet1) + abs($_points2-$_bet2);
			else $penalty = 0;
		}
		return $penalty;
	}

	function page_games() {
		global $wpdb, $options, $user_details, $language;

		$content = '';
		if (empty($this->campaign_details)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}
		$editor = !empty($user_details) && ($user_details['role'] == 'admin' || $this->campaign_details['owner_id'] == $user_details['id']);
		
		if ($language != 'en') $hl = '.'.$language;
		else $hl = '';
		if (file_exists(dirname(__FILE__).'/inc/countries'.$hl.'.php')) include(dirname(__FILE__).'/inc/countries'.$hl.'.php');
		else include(dirname(__FILE__).'/inc/countries.php');

		$games = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."t_games WHERE campaign_id = '".esc_sql($this->campaign_details['id'])."' AND deleted != '1' ORDER BY begin_time ASC", ARRAY_A);

		$title = esc_html__('Games', 't');
		$content .= '
		<h1>'.esc_html__('Games', 't').'</h1>
		'.($editor ? '<div class="table-funcbar">
			<div class="table-buttons"><a href="'.url('?page=add-game&cid='.$this->campaign_details['uuid']).'" class="button button-small"><i class="fas fa-plus"></i><span>'.esc_html__('Create New Game', 't').'</span></a></div>
		</div>' : '').'
		<div class="table table-row-hover">
			<table>
				<thead>
					<tr>
						<th>'.esc_html__('Game', 't').'</th>
						<th class="table-column-60">'.esc_html__('Score', 't').'</th>
						<th class="table-column-60">'.esc_html__('Penalty', 't').'</th>
						'.($editor ? '<th class="table-column-actions"></th>' : '').'
					</tr>
				</thead>
				<tbody>';
		if (sizeof($games) > 0) {
			foreach ($games as $game) {
				if ($game['team1_points'] >= 0 && $game['team2_points'] >= 0) {
					$sql = "SELECT SUM(t1.penalty) AS penalty FROM ".$wpdb->prefix."t_bets t1
							LEFT JOIN ".$wpdb->prefix."t_participants t2 ON t2.id = t1.participant_id
							LEFT JOIN ".$wpdb->prefix."users t3 ON t3.id = t2.user_id
						WHERE t1.game_id = '".esc_sql($game['id'])."' AND t1.deleted != '1' AND t2.deleted != '1' AND t3.deleted != '1' AND t3.status = 'active'";
					$tmp = $wpdb->get_row($sql, ARRAY_A);
					if (!empty($tmp)) $penalty = intval($tmp['penalty']);
					else $penalty = "-";
				} else $penalty = "-";
				$team1_name = array_key_exists($game['team1_slug'], $countries) ? $countries[$game['team1_slug']] : '-';
				if ($game['team1_points'] > $game['team2_points']) $team1_name = '<strong>'.$team1_name.'</strong>';
				$team2_name = array_key_exists($game['team2_slug'], $countries) ? $countries[$game['team2_slug']] : '-';
				if ($game['team2_points'] > $game['team1_points']) $team2_name = '<strong>'.$team2_name.'</strong>';
				if (file_exists(dirname(__FILE__).'/images/flags/'.$game['team1_slug'].'.png')) $team1_name = '<img class="totalizator-flag" src="'.plugins_url('/images/flags/'.$game['team1_slug'].'.png', __FILE__).'" alt="" />'.$team1_name;
				if (file_exists(dirname(__FILE__).'/images/flags/'.$game['team2_slug'].'.png')) $team2_name = '<img class="totalizator-flag" src="'.plugins_url('/images/flags/'.$game['team2_slug'].'.png', __FILE__).'" alt="" />'.$team2_name;
				$content .= '
					<tr>
						<td data-label="'.esc_html__('Game', 't').'"><a class="click-cell" href="'.url('?page=game-bets&cid='.$this->campaign_details['uuid']).'&game='.$game['uuid'].'"></a><label class="table-note">'.(array_key_exists($game['gametype'], $this->gametypes) ? esc_html($this->gametypes[$game['gametype']]) : '-').'</label><div class="totalizator-game">'.$team1_name.' : '.$team2_name.'</div><label class="table-note">'.esc_html(timestamp_string($game['begin_time'], $options['date-format'].' H:i')).'</label></td>
						<td data-label="'.esc_html__('Score', 't').'"><a class="click-cell" href="'.url('?page=game-bets&cid='.$this->campaign_details['uuid']).'&game='.$game['uuid'].'"></a>'.($game['team1_points'] >= 0 && $game['team2_points'] >= 0 ? esc_html($game['team1_points'].' : '.$game['team2_points']) : '-').'</td>
						<td data-label="'.esc_html__('Penalty', 't').'"><a class="click-cell" href="'.url('?page=game-bets&cid='.$this->campaign_details['uuid']).'&game='.$game['uuid'].'"></a>'.esc_html($penalty).'</td>
						'.($editor ? '<td data-label="'.esc_html__('Actions', 't').'">
							<div class="item-menu">
								<span><i class="fas fa-ellipsis-v"></i></span>
								<ul>
									<li><a href="'.url('?page=add-game&cid='.$this->campaign_details['uuid'].'&game='.$game['uuid']).'">'.esc_html__('Edit', 't').'</a></li>
									<li><a href="#" data-cid="'.esc_html($this->campaign_details['uuid']).'"  data-id="'.esc_html($game['uuid']).'" data-doing="'.esc_html__('Deleting...', 't').'" onclick="return totalizator_game_delete(this);">'.esc_html__('Delete', 't').'</a></li>
								</ul>
							</div>
						</td>': '').'
					</tr>';
				}
			}
			$content .= '
					<tr class="table-empty"'.(sizeof($games) > 0 ? ' style="display: none;"' : '').'><td colspan="'.($editor ? '4' : '3').'">'.esc_html__('List is empty.', 't').'</td></tr>
				</tbody>
			</table>
		</div>';
			
	
		return array('title' => $title, 'content' => $content);
	}

	function page_create_game() {
		global $wpdb, $options, $user_details, $language;

		$content = '';
		if (empty($this->campaign_details)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}
		$editor = !empty($user_details) && ($user_details['role'] == 'admin' || $this->campaign_details['owner_id'] == $user_details['id']);
		if (!$editor) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$game = null;
		if (array_key_exists('game', $_GET)) {
			$game_uid = preg_replace('/[^a-zA-Z0-9-]/', '', trim(stripslashes($_GET['game'])));
			$game = $wpdb->get_row("SELECT t1.* FROM ".$wpdb->prefix."t_games t1
					LEFT JOIN ".$wpdb->prefix."t_campaigns t2 ON t2.id = t1.campaign_id
				WHERE t1.uuid = '".esc_sql($game_uid)."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.id = '".esc_sql($this->campaign_details['id'])."'", ARRAY_A);
			if (empty($game)) {
				http_response_code(404);
				return array('title' => '404', 'content' => content_404());
			}
		}

		if ($language != 'en') $hl = '.'.$language;
		else $hl = '';
		if (file_exists(dirname(__FILE__).'/inc/countries'.$hl.'.php')) include(dirname(__FILE__).'/inc/countries'.$hl.'.php');
		else include(dirname(__FILE__).'/inc/countries.php');

		$title = !empty($game) ? esc_html__('Edit Game', 't') : esc_html__('Add Game', 't');
		$content .= '
		<h1>'.(!empty($game) ? esc_html__('Edit Game', 't') : esc_html__('Add Game', 't')).'</h1>
		<div class="form" id="join-campaign-form">
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Start time', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Select the time when the game is started. Make sure your tinezone is set properly.', 't').'
					</div>
				</div>
				<div class="form-content">
					<div class="columns">
						<div class="column column-30">
							<div class="input-box">
								<input class="errorable datetime" type="text" name="begin-time" placeholder="'.esc_html__('Begin', 't').'" value="'.(!empty($game) ? timestamp_string($game['begin_time'], $options['date-format'].' H:i') : '').'" data-default="'.(!empty($game) ? timestamp_string($game['begin_time'], 'Y-m-d H:i') : '').'" readonly="readonly">
							</div>
						</div>
						<div class="column column-70"></div>
					</div>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Type', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Type of the game.', 't').'
					</div>
				</div>
				<div class="form-content">
					<select class="errorable" name="gametype">
						<option value="">--- '.esc_html__('Select the type of the game','t').' ---</option>';
		foreach ($this->gametypes as $key => $label) {
			$content .= '<option value="'.esc_html($key).'"'.(!empty($game) && $game['gametype'] == $key ? ' selected="selected"' : '').'>'.esc_html($label).'</option>';
		}
		$content .= '
					</select>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Teams', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Select teams.', 't').'
					</div>
				</div>
				<div class="form-content">
					<div class="columns">
						<div class="column column-50">
							<div class="input-box">
								<select class="errorable" name="team1-slug">
									<option value="">--- '.esc_html__('Select the team','t').' ---</option>';
				foreach ($countries as $key => $label) {
					$content .= '<option value="'.esc_html($key).'"'.(!empty($game) && $game['team1_slug'] == $key ? ' selected="selected"' : '').'>'.esc_html($label).'</option>';
				}
				$content .= '
								</select>
							</div>
						</div>
						<div class="column column-50">
							<div class="input-box">
								<select class="errorable" name="team2-slug">
									<option value="">--- '.esc_html__('Select the team','t').' ---</option>';
		foreach ($countries as $key => $label) {
			$content .= '<option value="'.esc_html($key).'"'.(!empty($game) && $game['team2_slug'] == $key ? ' selected="selected"' : '').'>'.esc_html($label).'</option>';
		}
		$content .= '
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="form-row">
				<div class="form-label">
					<label>'.esc_html__('Score', 't').'</label>
				</div>
				<div class="form-tooltip">
					<i class="fas fa-question-circle form-tooltip-anchor"></i>
					<div class="form-tooltip-content">
						'.esc_html__('Set the final score.', 't').'
					</div>
				</div>
				<div class="form-content">
					<div class="columns">
						<div class="column column-50">
							<div class="input-box">
								<input class="errorable" type="text" name="team1-points" placeholder="..." value="'.(!empty($game) && $game['team1_points'] >= 0 ? $game['team1_points'] : '').'">
							</div>
						</div>
						<div class="column column-50">
							<div class="input-box">
								<input class="errorable" type="text" name="team2-points" placeholder="..." value="'.(!empty($game) && $game['team2_points'] >= 0 ? $game['team2_points'] : '').'">
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="form-row right-align">
				<input type="hidden" name="action" value="totalizator-game-save">
				<input type="hidden" name="cid" value="'.esc_html($this->campaign_details['uuid']).'">
				<input type="hidden" name="game-id" value="'.(!empty($game) ? $game['uuid'] : '').'">
				<a class="button" href="#" onclick="return save_form(this);" data-label="'.esc_html__('Save Details', 't').'">
					<span>'.esc_html__('Save Details', 't').'</span>
					<i class="fas fa-angle-right"></i>
				</a>
			</div>
		</div>';
		return array('title' => $title, 'content' => $content);
	}

	function game_save() {
		global $wpdb, $options, $user_details, $gmt_offset, $language;
		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if (empty($this->campaign_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}
		
		$editor = !empty($user_details) && ($user_details['role'] == 'admin' || $this->campaign_details['owner_id'] == $user_details['id']);
		if (!$editor) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}

		$mandatory_fields = array('cid', 'game-id', 'begin-time', 'gametype', 'team1-slug', 'team2-slug', 'team1-points', 'team2-points');
		$fields = array(
			'cid' => '',
			'game-id' => '',
			'begin-time' => '',
			'gametype' => '',
			'team1-slug' => '',
			'team2-slug' => '',
			'team1-points' => '',
			'team2-points' => ''
		);
		foreach ($mandatory_fields as $key) {
			if (array_key_exists($key, $_REQUEST)) {
				$fields[$key] = trim(stripslashes($_REQUEST[$key]));
			} else {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request: some fields are missing.', 't'));
				echo json_encode($return_data);
				exit;
			}
		}

		$fields['game-id'] = preg_replace('/[^a-zA-Z0-9-]/', '', $fields['game-id']);
		$fields['begin-time'] = validate_date($fields['begin-time'], $options['date-format']." H:i");

		$game = null;
		if (!empty($fields['game-id'])) {
			$game = $wpdb->get_row("SELECT t1.* FROM ".$wpdb->prefix."t_games t1
					LEFT JOIN ".$wpdb->prefix."t_campaigns t2 ON t2.id = t1.campaign_id
				WHERE t1.uuid = '".esc_sql($fields['game-id'])."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.id = '".esc_sql($this->campaign_details['id'])."'", ARRAY_A);
			if (empty($game)) {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
				echo json_encode($return_data);
				exit;
			}
		}

		if ($language != 'en') $hl = '.'.$language;
		else $hl = '';
		if (file_exists(dirname(__FILE__).'/inc/countries'.$hl.'.php')) include(dirname(__FILE__).'/inc/countries'.$hl.'.php');
		else include(dirname(__FILE__).'/inc/countries.php');

		$errors = array();
		if ($fields['begin-time'] === false) $errors['begin-time'] = esc_html__('Invalid time.', 't');
		if (mb_strlen($fields['team1-points']) > 0 && !ctype_digit($fields['team1-points'])) $errors['team1-points'] = esc_html__('Must be a not negative integer value.', 't');
		else if (mb_strlen($fields['team1-points']) == 0 && mb_strlen($fields['team2-points']) > 0) $errors['team1-points'] = esc_html__('Must be a not negative integer value.', 't');
		if (mb_strlen($fields['team2-points']) > 0 && !ctype_digit($fields['team2-points'])) $errors['team2-points'] = esc_html__('Must be a not negative integer value.', 't');
		else if (mb_strlen($fields['team2-points']) == 0 && mb_strlen($fields['team1-points']) > 0) $errors['team2-points'] = esc_html__('Must be a not negative integer value.', 't');
		if (!array_key_exists($fields['team1-slug'], $countries)) $errors['team1-slug'] = esc_html__('Must be a valid team.', 't');
		if (!array_key_exists($fields['team2-slug'], $countries)) $errors['team2-slug'] = esc_html__('Must be a valid team.', 't');
		if (!array_key_exists($fields['gametype'], $this->gametypes)) $errors['gametype'] = esc_html__('Must be a valid game type.', 't');

		if (!empty($errors)) {
			$return_data = array('status' => 'ERROR', 'errors' => $errors);
			echo json_encode($return_data);
			exit;
		}
		if (mb_strlen($fields['team1-points']) == 0) $fields['team1-points'] = -1;
		if (mb_strlen($fields['team2-points']) == 0) $fields['team2-points'] = -1;

		if (!empty($game)) {
			$wpdb->query("UPDATE ".$wpdb->prefix."t_games SET
				gametype = '".esc_sql($fields['gametype'])."',
				team1_slug = '".esc_sql($fields['team1-slug'])."',
				team2_slug = '".esc_sql($fields['team2-slug'])."',
				team1_points = '".esc_sql($fields['team1-points'])."',
				team2_points = '".esc_sql($fields['team2-points'])."',
				begin_time = '".esc_sql($fields['begin-time']->getTimestamp()-3600*$gmt_offset)."'
			WHERE id = '".esc_sql($game['id'])."' 
			");
			$game_id = $game['id'];
		} else {
			$wpdb->query("INSERT INTO ".$wpdb->prefix."t_games (
                uuid, 
                campaign_id, 
                gametype, 
                team1_slug, 
                team2_slug, 
                team1_points, 
                team2_points, 
                begin_time, 
                deleted, 
                created
            ) VALUES (
                '".esc_sql(uuid_v4())."',
                '".esc_sql($this->campaign_details['id'])."',
                '".esc_sql($fields['gametype'])."',
                '".esc_sql($fields['team1-slug'])."',
                '".esc_sql($fields['team2-slug'])."',
                '".esc_sql($fields['team1-points'])."',
                '".esc_sql($fields['team2-points'])."',
                '".esc_sql($fields['begin-time']->getTimestamp()-3600*$gmt_offset)."',
                '0',
                '".time()."'
            )");
			$game_id = $wpdb->insert_id;
		}
		$this->update_game_penalties($this->campaign_details['id'], $game_id, $this->campaign_details['nobet_penalty']);
		$_SESSION['success-message'] = esc_html__('Game details successfully saved.', 't');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Game details successfully saved.', 't'), 'url' => url('?page=games&cid='.$this->campaign_details['uuid']));
		echo json_encode($return_object);
		exit;
	}

	function page_game_bets() {
		global $wpdb, $options, $user_details, $language;

		$content = '';
		if (empty($this->campaign_details)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}
		
		$game = null;
		if (array_key_exists('game', $_GET)) {
			$game_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['game']);
			$game = $wpdb->get_row("SELECT t1.* FROM ".$wpdb->prefix."t_games t1
					LEFT JOIN ".$wpdb->prefix."t_campaigns t2 ON t2.id = t1.campaign_id
				WHERE t1.uuid = '".esc_sql($game_id)."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.id = '".esc_sql($this->campaign_details['id'])."'", ARRAY_A);
		}
		if (empty($game)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}
		if ($game['team1_points'] == '' && $game['team2_points'] == '' && $game['team1_points'] < 0 || $game['team2_points'] < 0) {
			$_SESSION['error-message'] = esc_html__('Final score was not set yet.', 't');
			header("Location: ".url('?page=games&cid='.$this->campaign_details['uuid']));
			exit;
		}

		if ($language != 'en') $hl = '.'.$language;
		else $hl = '';
		if (file_exists(dirname(__FILE__).'/inc/countries'.$hl.'.php')) include(dirname(__FILE__).'/inc/countries'.$hl.'.php');
		else include(dirname(__FILE__).'/inc/countries.php');

		$sql = "SELECT t1.*, t3.id AS bet_id, t3.team1_points AS bet_team1_points, t3.team2_points AS bet_team2_points, t3.penalty AS bet_penalty FROM ".$wpdb->prefix."t_participants t1
				LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
				LEFT JOIN ".$wpdb->prefix."t_bets t3 ON t1.id = t3.participant_id AND t3.game_id = '".esc_sql($game['id'])."'
			WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.status = 'active' AND t3.deleted != '1' AND t3.game_id = '".esc_sql($game['id'])."' ORDER BY t3.penalty ASC, t1.nickname ASC";
		$participants = $wpdb->get_results($sql, ARRAY_A);

		$title = (array_key_exists($game['team1_slug'], $countries) ? $countries[$game['team1_slug']] : '-').' : '.(array_key_exists($game['team2_slug'], $countries) ? $countries[$game['team2_slug']] : '-').($game['team1_points'] >= 0 && $game['team2_points'] >= 0 ? ' ('.esc_html($game['team1_points']).' : '.esc_html($game['team2_points']).')' : '');
		$content .= '
		<h1>'.esc_html($title).'</h1>
		<div class="table-funcbar">
			<div class="table-buttons"><a href="'.url('?page=games&cid='.$this->campaign_details['uuid']).'" class="button2 button-small"><i class="fas fa-angle-left"></i><span>'.esc_html__('Back', 't').'</span></a></div>
		</div>
		<div class="table">
			<table>
				<thead>
					<tr>
						<th class="table-column-30">'.esc_html__('#', 't').'</th>
						<th>'.esc_html__('Participant', 't').'</th>
						<th class="table-column-60">'.esc_html__('Bet', 't').'</th>
						<th class="table-column-60">'.esc_html__('Penalty', 't').'</th>
					</tr>
				</thead>
				<tbody>';
		$i = 0;
		$total = 0;
		if (sizeof($participants) > 0) {
			foreach ($participants as $participant) {
				if ($participant['bet_team1_points'] >= 0 && $participant['bet_team2_points'] >= 0 && $participant['bet_team1_points'] != "" && $participant['bet_team2_points'] != "") $betscore = $participant['bet_team1_points']." : ".$participant['bet_team2_points'];
				else $betscore = "-";
				$i++;
				$total += $participant['bet_penalty'];
				$content .= '
					<tr'.($this->campaign_details['participant_id'] == $participant['id'] ? ' class="table-highlight-row"' : '').'>
						<td data-label="'.esc_html__('#', 't').'">'.esc_html($i).'</td>
						<td data-label="'.esc_html__('Participant', 't').'">'.esc_html($participant['nickname']).'</td>
						<td data-label="'.esc_html__('Score', 't').'">'.esc_html($betscore).'</td>
						<td data-label="'.esc_html__('Penalty', 't').'">'.esc_html($participant['bet_penalty']).'</td>
					</tr>';
			}
		}
		$content .= '
					<tr class="table-empty"'.(sizeof($participants) > 0 ? ' style="display: none;"' : '').'><td colspan="4">'.esc_html__('List is empty.', 't').'</td></tr>
				</tbody>
			</table>
		</div>';
		return array('title' => $title, 'content' => $content);
	}

	function game_delete() {
		global $wpdb, $options, $user_details, $gmt_offset;
		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if (empty($this->campaign_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}
		
		$editor = !empty($user_details) && ($user_details['role'] == 'admin' || $this->campaign_details['owner_id'] == $user_details['id']);
		if (!$editor) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}

		$mandatory_fields = array('cid', 'game-id');
		$fields = array(
			'cid' => '',
			'game-id' => ''
		);
		foreach ($mandatory_fields as $key) {
			if (array_key_exists($key, $_REQUEST)) {
				$fields[$key] = trim(stripslashes($_REQUEST[$key]));
			} else {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request: some fields are missing.', 't'));
				echo json_encode($return_data);
				exit;
			}
		}

		$fields['game-id'] = preg_replace('/[^a-zA-Z0-9-]/', '', $fields['game-id']);

		$game = null;
		if (!empty($fields['game-id'])) {
			$game = $wpdb->get_row("SELECT t1.* FROM ".$wpdb->prefix."t_games t1
					LEFT JOIN ".$wpdb->prefix."t_campaigns t2 ON t2.id = t1.campaign_id
				WHERE t1.uuid = '".esc_sql($fields['game-id'])."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.id = '".esc_sql($this->campaign_details['id'])."'", ARRAY_A);
		}
		if (empty($game)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}
		$wpdb->query("UPDATE ".$wpdb->prefix."t_games SET deleted = '1' WHERE id = '".esc_sql($game['id'])."'");
		$_SESSION['success-message'] = esc_html__('Game successfully deleted.', 't');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Game successfully deleted.', 't'), 'url' => url('?page=games&cid='.$this->campaign_details['uuid']));
		echo json_encode($return_object);
		exit;
	}

	function page_participants() {
		global $wpdb, $options, $user_details;

		$content = '';
		if (empty($this->campaign_details)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}
		$editor = !empty($user_details) && ($user_details['role'] == 'admin' || $this->campaign_details['owner_id'] == $user_details['id']);

		$sql = "SELECT t1.*, t3.penalty, t4.guessed FROM ".$wpdb->prefix."t_participants t1
				LEFT JOIN ".$wpdb->prefix."users t2 ON t1.user_id = t2.id
				LEFT JOIN (SELECT SUM(st1.penalty) AS penalty, st1.participant_id FROM ".$wpdb->prefix."t_bets st1 
					JOIN ".$wpdb->prefix."t_games st2 ON st2.id = st1.game_id
					WHERE st2.deleted != '1' GROUP BY st1.participant_id) t3 ON t1.id = t3.participant_id
				LEFT JOIN (SELECT COUNT(st1.id) AS guessed, participant_id FROM ".$wpdb->prefix."t_bets st1 
					JOIN ".$wpdb->prefix."t_games st2 ON st1.game_id = st2.id 
					WHERE st2.deleted != '1' AND st1.penalty = '0' AND st1.team1_points >= '0' AND st1.team2_points >= '0' AND st2.team1_points >= '0' AND st2.team2_points >= '0' GROUP BY participant_id) t4 ON t1.id = t4.participant_id
			WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.status = 'active' ORDER BY t3.penalty ASC, t4.guessed DESC, t1.nickname ASC";
		
		$participants = $wpdb->get_results($sql, ARRAY_A);

		$title = esc_html__('Participants', 't');
		$content .= '
		<h1>'.esc_html__('Participants', 't').'</h1>
		<div class="table table-row-hover">
			<table>
				<thead>
					<tr>
						<th class="table-column-30">'.esc_html__('#', 't').'</th>
						<th>'.esc_html__('Participant', 't').'</th>
						<th class="table-column-60">'.esc_html__('Guessed', 't').'</th>
						<th class="table-column-60">'.esc_html__('Penalty', 't').'</th>
						<th class="table-column-60">'.esc_html__('Gap', 't').'</th>
						'.($editor ? '<th class="table-column-30"></th>' : '').'
					</tr>
				</thead>
				<tbody>';
		if (sizeof($participants) > 0) {
			$i = 0;
			$total_penalty = 0;
			$penalty_first = 0;
			foreach ($participants as $participant) {
				if (empty($participant['nickname'])) $participant['nickname'] = "-";
				$penalty = $participant['penalty'];
				$total_penalty += $penalty;
				if ($i == 0) $penalty_first = $penalty;
				$diff = $penalty - $penalty_first;
				if ($diff == 0) $diff = "-";
				if ($this->campaign_details['options']['guessing-enable'] == 'on' && $this->campaign_details['participant_guessing'] && $this->campaign_details['participant_guessing'] == 1 && $participant['guessing'] != 1) $pale_class = 'totalizator-pale';
				else $pale_class = '';
				$content .= '
					<tr class="'.$pale_class.($this->campaign_details['participant_id'] == $participant['id'] ? ' table-highlight-row' : '').'">
						<td data-label="#"><a class="click-cell" href="'.url('?page=participant-bets&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.($i+1).'</td>
						<td data-label="'.esc_html__('Participant', 't').'"><a class="click-cell" href="'.url('?page=participant-bets&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.esc_html($participant['nickname']).'</td>
						<td data-label="'.esc_html__('Guessed', 't').'"><a class="click-cell" href="'.url('?page=participant-bets&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.intval($participant['guessed']).'</td>
						<td data-label="'.esc_html__('Penalty', 't').'"><a class="click-cell" href="'.url('?page=participant-bets&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.intval($participant['penalty']).'</td>
						<td data-label="'.esc_html__('Gap', 't').'"><a class="click-cell" href="'.url('?page=participant-bets&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.$diff.'</td>
						'.($editor ? '<td data-label="'.esc_html__('Delete', 't').'"><a class="single-action" href="#" data-cid="'.esc_html($this->campaign_details['uuid']).'"  data-id="'.esc_html($participant['uuid']).'" onclick="return totalizator_participant_delete(this);"><i class="far fa-trash-alt"></i></a></td>': '').'
					</tr>';
					$i++;
				}
			}
			$content .= '
					<tr class="table-empty"'.(sizeof($participants) > 0 ? ' style="display: none;"' : '').'><td colspan="'.($editor ? '6' : '5').'">'.esc_html__('List is empty.', 't').'</td></tr>
				</tbody>
			</table>
		</div>';
			
		return array('title' => $title, 'content' => $content);
	}

	function participant_delete() {
		global $wpdb, $options, $user_details, $gmt_offset;
		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}
		if (empty($this->campaign_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}
		
		$editor = !empty($user_details) && ($user_details['role'] == 'admin' || $this->campaign_details['owner_id'] == $user_details['id']);
		if (!$editor) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}

		$mandatory_fields = array('cid', 'participant-id');
		$fields = array(
			'cid' => '',
			'participant-id' => ''
		);
		foreach ($mandatory_fields as $key) {
			if (array_key_exists($key, $_REQUEST)) {
				$fields[$key] = trim(stripslashes($_REQUEST[$key]));
			} else {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request: some fields are missing.', 't'));
				echo json_encode($return_data);
				exit;
			}
		}

		$fields['participant-id'] = preg_replace('/[^a-zA-Z0-9-]/', '', $fields['participant-id']);

		$participant = null;
		if (!empty($fields['participant-id'])) {
			$participant = $wpdb->get_row("SELECT t1.* FROM ".$wpdb->prefix."t_participants t1
					LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
					LEFT JOIN ".$wpdb->prefix."t_campaigns t3 ON t3.id = t1.campaign_id
				WHERE t1.uuid = '".esc_sql($fields['participant-id'])."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.status = 'active' AND t3.deleted != '1' AND t3.id = '".esc_sql($this->campaign_details['id'])."'", ARRAY_A);
		}
		if (empty($participant)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}
		$wpdb->query("UPDATE ".$wpdb->prefix."t_participants SET deleted = '1' WHERE id = '".esc_sql($participant['id'])."'");
		$_SESSION['success-message'] = esc_html__('Participant successfully deleted.', 't');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Participant successfully deleted.', 't'), 'url' => url('?page=participants&cid='.$this->campaign_details['uuid']));
		echo json_encode($return_object);
		exit;
	}

	function page_participant_bets() {
		global $wpdb, $options, $user_details, $language;

		$content = '';
		if (empty($this->campaign_details)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}
		$editor = !empty($user_details) && ($user_details['role'] == 'admin' || $this->campaign_details['owner_id'] == $user_details['id']);
		
		if ($language != 'en') $hl = '.'.$language;
		else $hl = '';
		if (file_exists(dirname(__FILE__).'/inc/countries'.$hl.'.php')) include(dirname(__FILE__).'/inc/countries'.$hl.'.php');
		else include(dirname(__FILE__).'/inc/countries.php');

		$participant = null;
		if (array_key_exists('participant', $_GET)) {
			$participant_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['participant']);
			$participant = $wpdb->get_row("SELECT t1.* FROM ".$wpdb->prefix."t_participants t1
					LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
					LEFT JOIN ".$wpdb->prefix."t_campaigns t3 ON t3.id = t1.campaign_id
				WHERE t1.uuid = '".esc_sql($participant_id)."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.status = 'active' AND t3.deleted != '1' AND t3.id = '".esc_sql($this->campaign_details['id'])."'", ARRAY_A);
		}
		if (empty($participant)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$sql = "SELECT t1.*, t2.team1_points AS bet_team1_points, t2.team2_points AS bet_team2_points, t2.penalty AS bet_penalty FROM ".$wpdb->prefix."t_games t1
				LEFT JOIN ".$wpdb->prefix."t_bets t2 ON t2.game_id = t1.id AND t2.participant_id = '".esc_sql($participant['id'])."' AND t2.deleted != '1'
			WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.deleted != '1' ORDER BY t1.begin_time ASC";
		$games = $wpdb->get_results($sql, ARRAY_A);

		$title = $participant['nickname'];
		$content .= '
		<h1>'.esc_html($participant['nickname']).'</h1>
		<div class="table-funcbar">
			<div class="table-buttons"><a href="'.url('?page=participants&cid='.$this->campaign_details['uuid']).'" class="button2 button-small"><i class="fas fa-angle-left"></i><span>'.esc_html__('Back', 't').'</span></a></div>
		</div>
		<div class="table">
			<table>
				<thead>
					<tr>
						<th>'.esc_html__('Game', 't').'</th>
						<th class="table-column-60">'.esc_html__('Bet', 't').'</th>
						<th class="table-column-60">'.esc_html__('Score', 't').'</th>
						<th class="table-column-60">'.esc_html__('Penalty', 't').'</th>
					</tr>
				</thead>
				<tbody>';
		if (sizeof($games) > 0) {
			$total_penalty = 0;
			foreach ($games as $game) {
				$team1_name = array_key_exists($game['team1_slug'], $countries) ? $countries[$game['team1_slug']] : '-';
				$team2_name = array_key_exists($game['team2_slug'], $countries) ? $countries[$game['team2_slug']] : '-';
				if ($game['team1_points'] >= 0 && $game['team2_points'] >= 0) {
					$score = $game['team1_points']." : ".$game['team2_points'];
					if ($game['team1_points'] > $game['team2_points']) $team1_name = '<strong>'.$team1_name.'</strong>';
					if ($game['team2_points'] > $game['team1_points']) $team2_name = '<strong>'.$team2_name.'</strong>';
				} else $score = "-";
				if ($game['bet_team1_points'] != '' && $game['bet_team2_points'] != '' && $game['bet_team1_points'] >= 0 && $game['bet_team2_points'] >= 0) {
					$bet_score = $game['bet_team1_points']." : ".$game['bet_team2_points'];
				} else $bet_score = '-';
				if ($score == "-") {
					$penalty = "-";
					$bet_score = "-";
				} else {
					$penalty = $game['bet_penalty'];
					$total_penalty += $penalty;
				}
		
				if (file_exists(dirname(__FILE__).'/images/flags/'.$game['team1_slug'].'.png')) $team1_name = '<img class="totalizator-flag" src="'.plugins_url('/images/flags/'.$game['team1_slug'].'.png', __FILE__).'" alt="" />'.$team1_name;
				if (file_exists(dirname(__FILE__).'/images/flags/'.$game['team2_slug'].'.png')) $team2_name = '<img class="totalizator-flag" src="'.plugins_url('/images/flags/'.$game['team2_slug'].'.png', __FILE__).'" alt="" />'.$team2_name;
				$content .= '
					<tr>
						<td data-label="'.esc_html__('Game', 't').'"><div class="totalizator-game">'.$team1_name.' : '.$team2_name.'</div></td>
						<td data-label="'.esc_html__('Bet', 't').'">'.esc_html($bet_score).'</td>
						<td data-label="'.esc_html__('Score', 't').'">'.esc_html($score).'</td>
						<td data-label="'.esc_html__('Penalty', 't').'">'.esc_html($penalty).'</td>
					</tr>';
				}
			}
			$content .= '
					<tr class="table-empty"'.(sizeof($games) > 0 ? ' style="display: none;"' : '').'><td colspan="4">'.esc_html__('List is empty.', 't').'</td></tr>
				</tbody>
			</table>
		</div>';
	
		return array('title' => $title, 'content' => $content);
	}

	function page_my_bets() {
		global $wpdb, $options, $user_details, $language;

		$content = '';
		if (empty($this->campaign_details) || empty($this->campaign_details['participant_id'])) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		if ($language != 'en') $hl = '.'.$language;
		else $hl = '';
		if (file_exists(dirname(__FILE__).'/inc/countries'.$hl.'.php')) include(dirname(__FILE__).'/inc/countries'.$hl.'.php');
		else include(dirname(__FILE__).'/inc/countries.php');

		$sql = "SELECT t1.*, t2.team1_points AS bet_team1_points, t2.team2_points AS bet_team2_points, t2.penalty AS bet_penalty FROM ".$wpdb->prefix."t_games t1
				LEFT JOIN ".$wpdb->prefix."t_bets t2 ON t2.game_id = t1.id AND t2.participant_id = '".esc_sql($this->campaign_details['participant_id'])."' AND t2.deleted != '1'
			WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.deleted != '1' ORDER BY t1.begin_time ASC";
		$games = $wpdb->get_results($sql, ARRAY_A);

		$title = esc_html__('My Bets', 't');
		$content .= '
		<h1>'.esc_html__('My Bets', 't').'</h1>
		<div class="table table-row-hover">
			<table>
				<thead>
					<tr>
						<th>'.esc_html__('Game', 't').'</th>
						<th class="table-column-60">'.esc_html__('Bet', 't').'</th>
						<th class="table-column-60">'.esc_html__('Score', 't').'</th>
						<th class="table-column-60">'.esc_html__('Penalty', 't').'</th>
					</tr>
				</thead>
				<tbody>';
		if (sizeof($games) > 0) {
			$total_penalty = 0;
			foreach ($games as $game) {
				$team1_name = array_key_exists($game['team1_slug'], $countries) ? $countries[$game['team1_slug']] : '-';
				$team2_name = array_key_exists($game['team2_slug'], $countries) ? $countries[$game['team2_slug']] : '-';
				if ($game['team1_points'] >= 0 && $game['team2_points'] >= 0) {
					$score = $game['team1_points']." : ".$game['team2_points'];
					if ($game['team1_points'] > $game['team2_points']) $team1_name = '<strong>'.$team1_name.'</strong>';
					if ($game['team2_points'] > $game['team1_points']) $team2_name = '<strong>'.$team2_name.'</strong>';
				} else $score = "-";
				if ($game['bet_team1_points'] != '' && $game['bet_team2_points'] != '' && $game['bet_team1_points'] >= 0 && $game['bet_team2_points'] >= 0) {
					$bet_score = $game['bet_team1_points']." : ".$game['bet_team2_points'];
				} else $bet_score = '-';
				if ($score == "-") {
					$penalty = "-";
				} else {
					$penalty = $game['bet_penalty'];
					$total_penalty += $penalty;
				}
		
				if (file_exists(dirname(__FILE__).'/images/flags/'.$game['team1_slug'].'.png')) $team1_name = '<img class="totalizator-flag" src="'.plugins_url('/images/flags/'.$game['team1_slug'].'.png', __FILE__).'" alt="" />'.$team1_name;
				if (file_exists(dirname(__FILE__).'/images/flags/'.$game['team2_slug'].'.png')) $team2_name = '<img class="totalizator-flag" src="'.plugins_url('/images/flags/'.$game['team2_slug'].'.png', __FILE__).'" alt="" />'.$team2_name;
				$content .= '
					<tr>
						<td data-label="'.esc_html__('Game', 't').'"><a class="click-cell" href="'.url('?page=add-bet&cid='.$this->campaign_details['uuid']).'&game='.$game['uuid'].'"></a><div class="totalizator-game">'.$team1_name.' : '.$team2_name.'</div></td>
						<td data-label="'.esc_html__('Bet', 't').'"><a class="click-cell" href="'.url('?page=add-bet&cid='.$this->campaign_details['uuid']).'&game='.$game['uuid'].'"></a>'.esc_html($bet_score).'</td>
						<td data-label="'.esc_html__('Score', 't').'"><a class="click-cell" href="'.url('?page=add-bet&cid='.$this->campaign_details['uuid']).'&game='.$game['uuid'].'"></a>'.esc_html($score).'</td>
						<td data-label="'.esc_html__('Penalty', 't').'"><a class="click-cell" href="'.url('?page=add-bet&cid='.$this->campaign_details['uuid']).'&game='.$game['uuid'].'"></a>'.esc_html($penalty).'</td>
					</tr>';
				}
			}
			$content .= '
					<tr class="table-empty"'.(sizeof($games) > 0 ? ' style="display: none;"' : '').'><td colspan="4">'.esc_html__('List is empty.', 't').'</td></tr>
				</tbody>
			</table>
		</div>';
	
		return array('title' => $title, 'content' => $content);
	}

	function page_set_bet() {
		global $wpdb, $options, $user_details, $language;

		$content = '';

		if (empty($this->campaign_details) || empty($this->campaign_details['participant_id'])) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$game = null;
		if (array_key_exists('game', $_GET)) {
			$game_uid = preg_replace('/[^a-zA-Z0-9-]/', '', trim(stripslashes($_GET['game'])));
			$game = $wpdb->get_row("SELECT t1.*, t3.team1_points AS bet_team1_points, t3.team2_points AS bet_team2_points FROM ".$wpdb->prefix."t_games t1
					LEFT JOIN ".$wpdb->prefix."t_campaigns t2 ON t2.id = t1.campaign_id
					LEFT JOIN ".$wpdb->prefix."t_bets t3 ON t3.game_id = t1.id AND t3.participant_id = '".esc_sql($this->campaign_details['participant_id'])."' AND t3.deleted != '1'
				WHERE t1.uuid = '".esc_sql($game_uid)."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.id = '".esc_sql($this->campaign_details['id'])."'", ARRAY_A);
		}

		if (empty($game)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		if ($game['begin_time'] < time()) {
			$_SESSION['error-message'] = esc_html__('You can not set the bet for already started game.', 't');
			header("Location: ".url('?page=my-bets&cid='.$this->campaign_details['uuid']));
			exit;
		}

		if ($language != 'en') $hl = '.'.$language;
		else $hl = '';
		if (file_exists(dirname(__FILE__).'/inc/countries'.$hl.'.php')) include(dirname(__FILE__).'/inc/countries'.$hl.'.php');
		else include(dirname(__FILE__).'/inc/countries.php');

		$team1_name = array_key_exists($game['team1_slug'], $countries) ? $countries[$game['team1_slug']] : '-';
		$team2_name = array_key_exists($game['team2_slug'], $countries) ? $countries[$game['team2_slug']] : '-';

		$title = (array_key_exists($game['team1_slug'], $countries) ? $countries[$game['team1_slug']] : '-').' : '.(array_key_exists($game['team2_slug'], $countries) ? $countries[$game['team2_slug']] : '-');
		$content .= '
		<h1>'.sprintf(esc_html__('My bet on %s', 't'), esc_html($title)).'</h1>
		<div class="form totalizator-bet-form" id="join-campaign-form">
			<div class="totalizator-bet-form-box">
				<div class="totalizator-bet-form-columns">
					<div class="totalizator-bet-form-column totalizator-bet-form-column-50">
						<div class="input-box">
							<div class="input-element">
								<input class="errorable" type="text" name="team1-points" placeholder="'.esc_html($team1_name).'" value="'.($game['bet_team1_points'] != "" && $game['bet_team1_points'] >= 0 ? intval($game['bet_team1_points']) : '').'">
							</div>
							<label>'.esc_html($team1_name).'</label>
						</div>
					</div>
					<div class="totalizator-bet-form-column" style="padding-top: 1em;">:</div>
					<div class="totalizator-bet-form-column totalizator-bet-form-column-50">
						<div class="input-box">
							<div class="input-element">
								<input class="errorable" type="text" name="team2-points" placeholder="'.esc_html($team2_name).'" value="'.($game['bet_team2_points'] != "" && $game['bet_team2_points'] >= 0 ? intval($game['bet_team2_points']) : '').'">
							</div>
							<label>'.esc_html($team2_name).'</label>
						</div>
					</div>
				</div>
				<div class="center-align">
					<input type="hidden" name="action" value="totalizator-bet-set">
					<input type="hidden" name="cid" value="'.esc_html($this->campaign_details['uuid']).'">
					<input type="hidden" name="game-id" value="'.esc_html($game['uuid']).'">
					<a class="button" href="#" onclick="return save_form(this);" data-label="'.esc_html__('Save Details', 't').'">
						<span>'.esc_html__('Save Details', 't').'</span>
						<i class="fas fa-angle-right"></i>
					</a>
				</div>
			</div>
		</div>';
	
		return array('title' => $title, 'content' => $content);
	}

	function bet_set() {
		global $wpdb, $options, $user_details;

		if (empty($user_details)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Oops. Please enter your account to perform this action.', 't'));
			echo json_encode($return_data);
			exit;
		}

		if (empty($this->campaign_details) || empty($this->campaign_details['participant_id'])) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}
		$mandatory_fields = array('cid', 'game-id', 'team1-points', 'team2-points');
		$fields = array(
			'cid' => '',
			'game-id' => '',
			'team1-points' => '',
			'team2-points' => ''
		);
		foreach ($mandatory_fields as $key) {
			if (array_key_exists($key, $_REQUEST)) {
				$fields[$key] = trim(stripslashes($_REQUEST[$key]));
			} else {
				$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request: some fields are missing.', 't'));
				echo json_encode($return_data);
				exit;
			}
		}
		$fields['game-id'] = preg_replace('/[^a-zA-Z0-9-]/', '', $fields['game-id']);

		$game = null;
		if (!empty($fields['game-id'])) {
			$game = $wpdb->get_row("SELECT t1.*, t3.id AS bet_id, t3.team1_points AS bet_team1_points, t3.team2_points AS bet_team2_points FROM ".$wpdb->prefix."t_games t1
					LEFT JOIN ".$wpdb->prefix."t_campaigns t2 ON t2.id = t1.campaign_id
					LEFT JOIN ".$wpdb->prefix."t_bets t3 ON t3.game_id = t1.id AND t3.participant_id = '".esc_sql($this->campaign_details['participant_id'])."' AND t3.deleted != '1'
				WHERE t1.uuid = '".esc_sql($fields['game-id'])."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.id = '".esc_sql($this->campaign_details['id'])."'", ARRAY_A);
		}
		if (empty($game)) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}

		if ($game['begin_time'] < time()) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('You can not set the bet for already started game.', 't'));
			echo json_encode($return_data);
			exit;
		}

		$errors = array();
		if (mb_strlen($fields['team1-points']) > 0 && !ctype_digit($fields['team1-points'])) $errors['team1-points'] = esc_html__('Must be a not negative integer value.', 't');
		else if (mb_strlen($fields['team1-points']) == 0 && mb_strlen($fields['team2-points']) > 0) $errors['team1-points'] = esc_html__('Must be a not negative integer value.', 't');
		else if (mb_strlen($fields['team1-points']) > 3) $errors['team1-points'] = esc_html__('The value is too huge.', 't');
		if (mb_strlen($fields['team2-points']) > 0 && !ctype_digit($fields['team2-points'])) $errors['team2-points'] = esc_html__('Must be a not negative integer value.', 't');
		else if (mb_strlen($fields['team2-points']) == 0 && mb_strlen($fields['team1-points']) > 0) $errors['team2-points'] = esc_html__('Must be a not negative integer value.', 't');
		else if (mb_strlen($fields['team2-points']) > 3) $errors['team2-points'] = esc_html__('The value is too huge.', 't');

		if (empty($game['bet_id']) && mb_strlen($fields['team1-points']) == 0 && mb_strlen($fields['team2-points']) == 0) {
			$errors['team1-points'] = esc_html__('Must be a not negative integer value.', 't');
			$errors['team2-points'] = esc_html__('Must be a not negative integer value.', 't');
		}

		if (!empty($errors)) {
			$return_data = array('status' => 'ERROR', 'errors' => $errors);
			echo json_encode($return_data);
			exit;
		}
		if (mb_strlen($fields['team1-points']) == 0) $fields['team1-points'] = -1;
		if (mb_strlen($fields['team2-points']) == 0) $fields['team2-points'] = -1;

		if (!empty($game['bet_id'])) {
			$wpdb->query("UPDATE ".$wpdb->prefix."t_bets SET team1_points = '".esc_sql($fields['team1-points'])."', team2_points = '".esc_sql($fields['team2-points'])."' WHERE id = '".esc_sql($game['bet_id'])."'");
		} else {
			$wpdb->query("INSERT INTO ".$wpdb->prefix."t_bets (
				game_id, 
				participant_id, 
				team1_points,
				team2_points, 
				penalty,
				deleted,
				created
			) VALUES (
				'".esc_sql($game['id'])."',
				'".esc_sql($this->campaign_details['id'])."',
				'".esc_sql($fields['team1-points'])."',
				'".esc_sql($fields['team2-points'])."',
				'0',
				'0',
				'".time()."'
			)");
		}

		$_SESSION['success-message'] = esc_html__('Bet successfully saved.', 't');
		$return_object = array('status' => 'OK', 'message' => esc_html__('Bet successfully saved.', 't'), 'url' => url('?page=my-bets&cid='.$this->campaign_details['uuid']));
		echo json_encode($return_object);
		exit;
	}

	function page_guessing() {
		global $wpdb, $options, $user_details;

		$content = '';
		if (empty($this->campaign_details) || ((empty($this->campaign_details['participant_id']) || $this->campaign_details['participant_guessing'] != 1) && $this->campaign_details['owner_id'] != $user_details['id'] && $user_details['role'] != 'admin')) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$current_time = time();
		if ($current_time < $this->campaign_details['begin_date']) {
			$_SESSION['error-message'] = esc_html__('Guessing starts soon.', 't');
			header("Location: ".url('?page=games&cid='.$this->campaign_details['uuid']));
			exit;
		} else {
			if ($this->campaign_details['owner_id'] != $user_details['id'] && $user_details['role'] != 'admin') {
				if ($current_time < $this->campaign_details['end_date']) {
					header("Location: ".url('?page=set-guessing&cid='.$this->campaign_details['uuid']));
					exit;
				} else if ($current_time < $this->campaign_details['options']['guessing-disclosure-date']) {
					$_SESSION['error-message'] = esc_html__('Guessing results will be available soon.', 't');
					header("Location: ".url('?page=games&cid='.$this->campaign_details['uuid']));
					exit;
				}
			}
		}

		$sql = "SELECT t1.*, t2.name AS user_name, t3.total FROM ".$wpdb->prefix."t_participants t1
				LEFT JOIN ".$wpdb->prefix."users t2 ON t1.user_id = t2.id
				LEFT JOIN (SELECT COUNT(st1.id) AS total, participant_id FROM ".$wpdb->prefix."t_guessing st1 JOIN ".$wpdb->prefix."t_participants st2 ON st2.id = st1.p1_id WHERE st1.p1_id = st1.p2_id AND st1.p1_id != st1.participant_id AND st2.guessing = '1' GROUP BY participant_id) t3 ON t1.id = t3.participant_id
			WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.guessing = '1' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.status = 'active' ORDER BY t3.total DESC";
		$participants = $wpdb->get_results($sql, ARRAY_A);

		$title = esc_html__('Guessing Results', 't');
		$content .= '
		<h1>'.esc_html__('Guessing Results', 't').'</h1>
		<h2>'.esc_html__('Who guesses better', 't').'</h2>
		<div class="table table-row-hover">
			<table>
				<thead>
					<tr>
						<th class="table-column-30">'.esc_html__('#', 't').'</th>
						<th>'.esc_html__('Participant', 't').'</th>
						<th class="table-column-60">'.esc_html__('Guessed', 't').'</th>
						<th>'.esc_html__('Guessed Participants', 't').'</th>
					</tr>
				</thead>
				<tbody>';
		if (sizeof($participants) > 0) {
			$i = 0;
			foreach ($participants as $participant) {
				$sql = "SELECT t2.id, t2.nickname, t3.name AS user_name FROM ".$wpdb->prefix."t_guessing t1
						JOIN ".$wpdb->prefix."t_participants t2 ON t2.id = t1.p1_id
						JOIN ".$wpdb->prefix."users t3 ON t3.id = t2.user_id
					WHERE t1.participant_id = '".$participant['id']."' AND t1.p1_id = t1.p2_id AND t1.p1_id != t1.participant_id AND t2.guessing = '1'";
				$tmp = $wpdb->get_results($sql, ARRAY_A);
				$plist = array();
				foreach ($tmp as $tmp_record) {
					$plist[] = esc_html($tmp_record["nickname"]).' <span class="totalizator-pale"> ('.esc_html($tmp_record["user_name"]).')</span>';
				}
				if (sizeof($plist) > 0) $plist = implode(', ', $plist);
				else $plist = '-';
		
				$content .= '
					<tr>
						<td data-label="'.esc_html__('#', 't').'"><a class="click-cell" href="'.url('?page=guessing-details&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.($i+1).'</td>
						<td data-label="'.esc_html__('Participant', 't').'"><a class="click-cell" href="'.url('?page=guessing-details&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.esc_html($participant['nickname']).' <span class="totalizator-pale">('.esc_html($participant['user_name']).')</span></td>
						<td data-label="'.esc_html__('Guessed', 't').'"><a class="click-cell" href="'.url('?page=guessing-details&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.intval($participant['total']).'</td>
						<td data-label="'.esc_html__('Guessed Participants', 't').'"><a class="click-cell" href="'.url('?page=guessing-details&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.$plist.'</td>
					</tr>';
				$i++;
			}
		}
		$content .= '
					<tr class="table-empty"'.(sizeof($participants) > 0 ? ' style="display: none;"' : '').'><td colspan="4">'.esc_html__('List is empty.', 't').'</td></tr>
				</tbody>
			</table>
		</div>';
		
		$sql = "SELECT t1.*, t2.name AS user_name, t3.total FROM ".$wpdb->prefix."t_participants t1
				LEFT JOIN ".$wpdb->prefix."users t2 ON t1.user_id = t2.id
				LEFT JOIN (SELECT COUNT(st1.id) AS total, p1_id FROM ".$wpdb->prefix."t_guessing st1 JOIN ".$wpdb->prefix."t_participants st2 ON st2.id = st1.participant_id WHERE st1.p1_id = st1.p2_id AND st1.p1_id != st1.participant_id AND st2.guessing = '1' GROUP BY st1.p1_id) t3 ON t1.id = t3.p1_id
			WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.guessing = '1' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.status = 'active' ORDER BY t3.total ASC";
		$participants = $wpdb->get_results($sql, ARRAY_A);
		
		$content .= '
		<h2>'.esc_html__('Who was guessed less often', 't').'</h2>
		<div class="table table-row-hover">
			<table>
				<thead>
					<tr>
						<th class="table-column-30">'.esc_html__('#', 't').'</th>
						<th>'.esc_html__('Participant', 't').'</th>
						<th class="table-column-60">'.esc_html__('Guessed', 't').'</th>
						<th>'.esc_html__('Who Guessed', 't').'</th>
						<th>'.esc_html__('Who they thought', 't').'</th>
					</tr>
				</thead>
				<tbody>';
		if (sizeof($participants) > 0) {
			$i = 0;
			foreach ($participants as $participant) {
				$sql = "SELECT t2.id, t2.nickname, t3.name AS user_name FROM ".$wpdb->prefix."t_guessing t1
						JOIN ".$wpdb->prefix."t_participants t2 ON t2.id = t1.participant_id
						JOIN ".$wpdb->prefix."users t3 ON t3.id = t2.user_id
					WHERE t1.p1_id = '".$participant['id']."' AND t1.p1_id = t1.p2_id AND t1.p1_id != t1.participant_id AND t2.guessing = '1'";
				$tmp = $wpdb->get_results($sql, ARRAY_A);
				$plist = array();
				foreach ($tmp as $tmp_record) {
					$plist[] = esc_html($tmp_record["nickname"]).' <span class="totalizator-pale"> ('.esc_html($tmp_record["user_name"]).')</span>';
				}
				if (sizeof($plist) > 0) $plist = implode(', ', $plist);
				else $plist = '-';

				$sql = "SELECT COUNT(*) AS total, t2.nickname FROM ".$wpdb->prefix."t_guessing t1
						JOIN ".$wpdb->prefix."t_participants t2 ON t2.id = t1.p2_id
					WHERE t1.p1_id = '".$participant['id']."' AND t1.participant_id != t1.p1_id AND t2.guessing = '1' GROUP BY t1.p2_id";
				$tmp = $wpdb->get_results($sql, ARRAY_A);
				$plist2 = array();
				foreach ($tmp as $tmp_record) {
					$plist2[] = esc_html($tmp_record["nickname"]).' <span class="totalizator-pale"> ('.esc_html($tmp_record["total"]).')</span>';
				}
				if (sizeof($plist2) > 0) $plist2 = implode(', ', $plist2);
				else $plist2 = '-';
				
				$content .= '
					<tr>
						<td data-label="'.esc_html__('#', 't').'"><a class="click-cell" href="'.url('?page=guessing-details&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.($i+1).'</td>
						<td data-label="'.esc_html__('Participant', 't').'"><a class="click-cell" href="'.url('?page=guessing-details&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.esc_html($participant['user_name']).' <span class="totalizator-pale">('.esc_html($participant['nickname']).')</span></td>
						<td data-label="'.esc_html__('Guessed', 't').'"><a class="click-cell" href="'.url('?page=guessing-details&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.intval($participant['total']).'</td>
						<td data-label="'.esc_html__('Who Guessed', 't').'"><a class="click-cell" href="'.url('?page=guessing-details&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.$plist.'</td>
						<td data-label="'.esc_html__('Who they thought', 't').'"><a class="click-cell" href="'.url('?page=guessing-details&cid='.$this->campaign_details['uuid']).'&participant='.$participant['uuid'].'"></a>'.$plist2.'</td>
					</tr>';
				$i++;
			}
		}
		$content .= '
					<tr class="table-empty"'.(sizeof($participants) > 0 ? ' style="display: none;"' : '').'><td colspan="4">'.esc_html__('List is empty.', 't').'</td></tr>
				</tbody>
			</table>
		</div>';
	
		return array('title' => $title, 'content' => $content);
	}

	function page_guessing_details() {
		global $wpdb, $options, $user_details;

		$content = '';
		if (empty($this->campaign_details) || ((empty($this->campaign_details['participant_id']) || $this->campaign_details['participant_guessing'] != 1) && $this->campaign_details['owner_id'] != $user_details['id'] && $user_details['role'] != 'admin')) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$current_time = time();
		if ($current_time < $this->campaign_details['begin_date']) {
			$_SESSION['error-message'] = esc_html__('Guessing starts soon.', 't');
			header("Location: ".url('?page=games&cid='.$this->campaign_details['uuid']));
			exit;
		} else {
			if ($this->campaign_details['owner_id'] != $user_details['id'] && $user_details['role'] != 'admin') {
				if ($current_time < $this->campaign_details['end_date']) {
					header("Location: ".url('?page=set-guessing&cid='.$this->campaign_details['uuid']));
					exit;
				} else if ($current_time < $this->campaign_details['options']['guessing-disclosure-date']) {
					$_SESSION['error-message'] = esc_html__('Guessing results will be available soon.', 't');
					header("Location: ".url('?page=games&cid='.$this->campaign_details['uuid']));
					exit;
				}
			}
		}

		$participant = null;
		if (array_key_exists('participant', $_GET)) {
			$participant_id = preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['participant']);
			$participant = $wpdb->get_row("SELECT t1.*, t2.name AS user_name FROM ".$wpdb->prefix."t_participants t1
					LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
					LEFT JOIN ".$wpdb->prefix."t_campaigns t3 ON t3.id = t1.campaign_id
				WHERE t1.uuid = '".esc_sql($participant_id)."' AND t1.deleted != '1' AND t1.guessing = '1' AND t2.deleted != '1' AND t2.status = 'active' AND t3.deleted != '1' AND t3.id = '".esc_sql($this->campaign_details['id'])."'", ARRAY_A);
		}
		if (empty($participant)) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$sql = "SELECT t1.*, t2.name AS user_name, t2.email, t3.p1_id, t3.p2_id, t4.nickname AS g_nickname FROM ".$wpdb->prefix."t_participants t1
				LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
				LEFT JOIN ".$wpdb->prefix."t_guessing t3 ON t1.id = t3.p1_id AND t3.participant_id = '".esc_sql($participant['id'])."'
				LEFT JOIN ".$wpdb->prefix."t_participants t4 ON t4.id = t3.p2_id AND t4.guessing = '1' AND t4.deleted != '1'
			WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.id != '".esc_sql($participant['id'])."' AND t1.guessing = '1' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.status = 'active' ORDER BY t2.name ASC";

		$participants = $wpdb->get_results($sql, ARRAY_A);

		$title = sprintf(esc_html__('Guessing of %s (%s)', 't'), esc_html($participant['nickname']), esc_html($participant['user_name']));
		$content .= '
		<h1>'.$title.'</h1>
		<div class="table-funcbar">
			<div class="table-buttons"><a href="'.url('?page=guessing&cid='.$this->campaign_details['uuid']).'" class="button2 button-small"><i class="fas fa-angle-left"></i><span>'.esc_html__('Back', 't').'</span></a></div>
		</div>
		<div class="table">
			<table>
				<thead>
					<tr>
						<th>'.esc_html__('Nickname', 't').'</th>
						<th>'.esc_html__('User', 't').'</th>
						<th class="table-column-30"></th>
					</tr>
				</thead>
				<tbody>';
		if (sizeof($participants) > 0) {
			foreach ($participants as $p) {
				$content .= '
					<tr>
						<td data-label="'.esc_html__('Nickname', 't').'" class="'.(!empty($p['p1_id']) && $p['p1_id'] == $p['p2_id'] ? 'color-success' : 'color-danger').'">'.(!empty($p['g_nickname']) ? esc_html($p['g_nickname']) : '-').'</td>
						<td data-label="'.esc_html__('User', 't').'" class="'.(!empty($p['p1_id']) && $p['p1_id'] == $p['p2_id'] ? 'color-success' : 'color-danger').'">'.esc_html($p['user_name']).'</td>
						<td data-label="">'.(!empty($p['p1_id']) && $p['p1_id'] == $p['p2_id'] ? '<i class="fas fa-check color-success"></i>' : '<i class="fas fa-times color-danger"></i>').'</td>
					</tr>';
			}
		}
		$content .= '
					<tr class="table-empty"'.(sizeof($participants) > 0 ? ' style="display: none;"' : '').'><td colspan="3">'.esc_html__('List is empty.', 't').'</td></tr>
				</tbody>
			</table>
		</div>';
	
		return array('title' => $title, 'content' => $content);
	}

	function page_set_guessing() {
		global $wpdb, $options, $user_details;

		$content = '';
		if (empty($this->campaign_details) || empty($this->campaign_details['participant_id']) || $this->campaign_details['participant_guessing'] != 1) {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$current_time = time();
		if ($current_time < $this->campaign_details['begin_date']) {
			$_SESSION['error-message'] = esc_html__('Guessing starts soon.', 't');
			header("Location: ".url('?page=games&cid='.$this->campaign_details['uuid']));
			exit;
		} else if ($current_time > $this->campaign_details['options']['guessing-disclosure-date']) {
			header("Location: ".url('?page=guessing&cid='.$this->campaign_details['uuid']));
			exit;
		} else if ($current_time > $this->campaign_details['end_date']) {
			$_SESSION['error-message'] = esc_html__('Guessing results will be available soon.', 't');
			header("Location: ".url('?page=set-guessing&cid='.$this->campaign_details['uuid']));
			exit;
		}

		$sql = "SELECT t1.*, t2.uuid AS user_uuid, t2.name AS user_name, t3.p1_id, t3.p2_id FROM ".$wpdb->prefix."t_participants t1
				LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
				LEFT JOIN ".$wpdb->prefix."t_guessing t3 ON t1.id = t3.p1_id AND t3.participant_id = '".esc_sql($this->campaign_details['participant_id'])."'
			WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.id != '".esc_sql($this->campaign_details['participant_id'])."' AND t1.guessing = '1' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.status = 'active' ORDER BY t2.name ASC";

		$participants = $wpdb->get_results($sql, ARRAY_A);
		$participants2 = $participants;
		shuffle($participants2);

		$title = esc_html__('My Guessing', 't');
		$content .= '
		<h1>'.$title.'</h1>
		<div class="form">
			<div class="table">
				<table>
					<thead>
						<tr>
							<th>'.esc_html__('User', 't').'</th>
							<th>'.esc_html__('Nickname', 't').'</th>
						</tr>
					</thead>
					<tbody>';
		if (sizeof($participants) > 0) {
			foreach ($participants as $p) {
				$content .= '
						<tr>
							<td data-label="'.esc_html__('User', 't').'">'.(!empty($p['user_name']) ? esc_html($p['user_name']) : '-').'</td>
							<td data-label="'.esc_html__('Nickname', 't').'">
								<select name="user['.$p['user_uuid'].']">
									<option value="">--- '.esc_html('Select Nickname', 't').' ---</option>';
				foreach ($participants2 as $p2) {
					$content .= '
									<option value="'.$p2['uuid'].'"'.($p['p2_id'] == $p2['id'] ? ' selected="selected"' : '').'>'.$p2['nickname'].'</option>';
				}
				$content .= '
								</select>
							</td>
						</tr>';
			}
		}
		$content .= '
						<tr class="table-empty"'.(sizeof($participants) > 0 ? ' style="display: none;"' : '').'><td colspan="2">'.esc_html__('List is empty.', 't').'</td></tr>
					</tbody>
				</table>
			</div>
			'.(sizeof($participants) > 0 ? '<div class="table-funcbar">
				<div class="table-buttons">
					<input type="hidden" name="action" value="totalizator-guessing-set">
					<input type="hidden" name="cid" value="'.esc_html($this->campaign_details['uuid']).'">
					<a class="button" href="#" onclick="return save_form(this);" data-label="'.esc_html__('Save Details', 't').'">
						<span>'.esc_html__('Save Details', 't').'</span>
						<i class="fas fa-angle-right"></i>
					</a>
				</div>
			</div>' : '').'
		</div>';
	
		return array('title' => $title, 'content' => $content);
	}

	function guessing_set() {
		global $wpdb, $options, $user_details;

		$content = '';
		if (empty($this->campaign_details) || empty($this->campaign_details['participant_id']) || $this->campaign_details['participant_guessing'] != 1) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}

		$current_time = time();
		if ($current_time < $this->campaign_details['begin_date']) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Guessing is not available yet.', 't'));
			echo json_encode($return_data);
			exit;
		} else if ($current_time > $this->campaign_details['end_date']) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Guessing is already finished.', 't'));
			echo json_encode($return_data);
			exit;
		}

		if (!array_key_exists('user', $_REQUEST) && !is_array($_REQUEST['user'])) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}

		$guessing = array();
		foreach ($_REQUEST['user'] as $user_uuid => $participant_uuid) {
			$user_uuid = preg_replace('/[^a-zA-Z0-9-]/', '', $user_uuid);
			$participant_uuid = preg_replace('/[^a-zA-Z0-9-]/', '', $participant_uuid);
			$participant1 = $wpdb->get_row("SELECT t1.* FROM ".$wpdb->prefix."t_participants t1
					LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
				WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.deleted != '1' AND t1.guessing = '1' AND t2.uuid = '".esc_sql($user_uuid)."' AND t2.deleted != '1' AND t2.status = 'active'", ARRAY_A);
			$participant2 = $wpdb->get_row("SELECT t1.* FROM ".$wpdb->prefix."t_participants t1
					LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
				WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.uuid = '".esc_sql($participant_uuid)."' AND t1.deleted != '1' AND t1.guessing = '1' AND t2.deleted != '1' AND t2.status = 'active'", ARRAY_A);
			if (!empty($participant1)) {
				//echo $participant1['id'];
				$guessing = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."t_guessing WHERE participant_id = '".$this->campaign_details['participant_id']."' AND p1_id = '".esc_sql($participant1['id'])."'", ARRAY_A);
				if (!empty($guessing)) {
					$wpdb->query("UPDATE ".$wpdb->prefix."t_guessing SET p2_id = '".intval($participant2['id'])."' WHERE id = '".esc_sql($guessing['id'])."'");
				} else {
					$wpdb->query("INSERT INTO ".$wpdb->prefix."t_guessing (
						participant_id, 
						p1_id,
						p2_id
					) VALUES (
						'".esc_sql($this->campaign_details['participant_id'])."',
						'".intval($participant1['id'])."',
						'".intval($participant2['id'])."'
					)");
				}
			}
		}
		$return_object = array('status' => 'OK', 'message' => esc_html__('Guessing successfully saved.', 't'));
		echo json_encode($return_object);
		exit;
	}

	function page_guessing_participants() {
		global $wpdb, $options, $user_details;

		$content = '';
		if (empty($user_details) || empty($this->campaign_details) || ($this->campaign_details['owner_id']) != $user_details['id'] && $user_details['role'] != 'admin') {
			http_response_code(404);
			return array('title' => '404', 'content' => content_404());
		}

		$current_time = time();
		if ($current_time < $this->campaign_details['begin_date']) {
			$_SESSION['error-message'] = esc_html__('Guessing starts soon.', 't');
			header("Location: ".url('?page=games&cid='.$this->campaign_details['uuid']));
			exit;
		}

		$sql = "SELECT t1.*, t2.uuid AS user_uuid, t2.name AS user_name FROM ".$wpdb->prefix."t_participants t1
				LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
			WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND t1.deleted != '1' AND t2.deleted != '1' AND t2.status = 'active' ORDER BY t2.name ASC";
		$participants = $wpdb->get_results($sql, ARRAY_A);

		$title = esc_html__('Guessing Participants', 't');
		$content .= '
		<h1>'.$title.'</h1>
		<div class="form">
			<div class="table">
				<table>
					<tbody>';
		if (sizeof($participants) > 0) {
			foreach ($participants as $p) {
				$content .= '
						<tr>
							<td data-label="'.esc_html__('User', 't').'">'.(!empty($p['user_name']) ? esc_html($p['user_name']) : '-').'</td>
							<td class="right-align" data-label="'.esc_html__('Enable', 't').'">
								<input class="checkbox-toggle" type="checkbox" value="on" id="user-'.esc_html($p['user_uuid']).'" name="user['.esc_html($p['user_uuid']).']"'.($p['guessing'] == 1 ? ' checked="checked"' : '').'><label for="user-'.esc_html($p['user_uuid']).'"></label>
							</td>
						</tr>';
			}
		}
		$content .= '
						<tr class="table-empty"'.(sizeof($participants) > 0 ? ' style="display: none;"' : '').'><td colspan="2">'.esc_html__('List is empty.', 't').'</td></tr>
					</tbody>
				</table>
			</div>
			'.(sizeof($participants) > 0 ? '<div class="table-funcbar">
				<div class="table-buttons">
					<input type="hidden" name="action" value="totalizator-guessing-participants-save">
					<input type="hidden" name="cid" value="'.esc_html($this->campaign_details['uuid']).'">
					<a class="button" href="#" onclick="return save_form(this);" data-label="'.esc_html__('Save Details', 't').'">
						<span>'.esc_html__('Save Details', 't').'</span>
						<i class="fas fa-angle-right"></i>
					</a>
				</div>
			</div>' : '').'
		</div>';
	
		return array('title' => $title, 'content' => $content);
	}

	function guessing_participants_save() {
		global $wpdb, $options, $user_details;

		$content = '';
		if (empty($user_details) || empty($this->campaign_details) || ($this->campaign_details['owner_id']) != $user_details['id'] && $user_details['role'] != 'admin') {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}

		$current_time = time();
		if ($current_time < $this->campaign_details['begin_date']) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Guessing is not available yet.', 't'));
			echo json_encode($return_data);
			exit;
		}

		if (!array_key_exists('user', $_REQUEST) && !is_array($_REQUEST['user'])) {
			$return_data = array('status' => 'WARNING', 'message' => esc_html__('Invalid request.', 't'));
			echo json_encode($return_data);
			exit;
		}

		$wpdb->query("UPDATE ".$wpdb->prefix."t_participants SET guessing = '0' WHERE campaign_id = '".esc_sql($this->campaign_details['id'])."' AND deleted != '1'");
		foreach ($_REQUEST['user'] as $user_uuid => $value) {
			$sql = "SELECT t1.*, t2.uuid AS user_uuid, t2.name AS user_name FROM ".$wpdb->prefix."t_participants t1
					LEFT JOIN ".$wpdb->prefix."users t2 ON t2.id = t1.user_id
				WHERE t1.campaign_id = '".esc_sql($this->campaign_details['id'])."' AND  t1.deleted != '1' AND t2.uuid = '".esc_sql($user_uuid)."' AND t2.deleted != '1' AND t2.status = 'active' ORDER BY t2.name ASC";
			$participant = $wpdb->get_row($sql, ARRAY_A);
			if (!empty($participant)) {
				$wpdb->query("UPDATE ".$wpdb->prefix."t_participants SET guessing = '1' WHERE id = '".esc_sql($participant['id'])."'");
			}
		}

		$return_object = array('status' => 'OK', 'message' => esc_html__('Guessing participants successfully saved.', 't'));
		echo json_encode($return_object);
		exit;
	}

	function page_faq() {
		global $wpdb, $options, $user_details;
		$content = '';

		$title = esc_html__('Frequently Asked Questions', 't');
		$content .= '
		<h1>'.$title.'</h1>
		<div class="totalizator-faq-container">
			<h2>'.esc_html__('What is it and how it works?', 't').'</h2>
			<p>
			'.esc_html__('These are totalizators dedicated to important championships (for example, the FIFA World Cup or the African Ice Hockey Championship).
			Everyone can become a member of any totalizator and check how much he knows the topic better than others.
			The idea is pretty simple: totalizator participants try to guess the score of each match, and, depending on their sense of foresight, receive penalty points.
			The winner of the totalizator is the participant with the lowest number of penalty points.', 't').'
			</p>
			
			<h2>'.esc_html__('How to join totalizator?', 't').'</h2>
			<ol>
				<li>'.esc_html__('Register and log into your account.', 't').'</li>
				<li>'.esc_html__('On the main page, select an active totalizator and click the "Join Totalizator" button.', 't').'</li>
				<li>'.esc_html__('Enter your nickname and click the "Join Totalizator" button again.', 't').'</li>
			</ol>
			<p>'.esc_html__('Voila! You have become a participant.', 't').'</p>
			
			<h2>'.esc_html__('What is a nickname for?', 't').'</h2>
			<p>'.esc_html__('Just for fun. If you play with friends, colleagues or acquaintances, no one will know who is hiding behind a nickname.
			If the "Guessing" option is enabled for the totalizator, then you can also try to guess the real participants hiding behind nicknames.
			At the end of the totalizator, real names will be revealed.', 't').'</p>
			
			<h2>'.esc_html__('Can I change my nickname?', 't').'</h2>
			<p>'.esc_html__('Yes. You can do this before the start of the totalizator by clicking on your current nickname in the header of the site.', 't').'</p>
			
			<h2>'.esc_html__('How do I place a bet?', 't').'</h2>
			<ol>
				<li>'.esc_html__('On the main page, select an active totalizator.', 't').'</li>
				<li>'.esc_html__('Click "My Bets" in the top menu.', 't').'</li>
				<li>'.esc_html__('Select a match by clicking on the appropriate line of the table and place your bet.', 't').'</li>
			</ol>
			
			<h2>'.esc_html__('How do I change my bet?', 't').'</h2>
			<p>'.esc_html__('Exactly as described above. The bet can only be changed before the start of the match.', 't').'</p>
			
			<h2>'.esc_html__('What if I have not placed a bet before the start of the match?', 't').'</h2>
			<p>'.esc_html__('In this case, you will get a fixed number of penalty points (depending on the totalizator settings, usually 26).', 't').'</p>
			
			<h2>'.esc_html__('How are penalty points calculated?', 't').'</h2>
			<p>'.esc_html__('The following formula is used for the calculation:', 't').'</p>
			<p><strong>P = N + |X - X1| + |Y - Y1|</strong></p>
			<p><strong>P</strong> - '.esc_html__('penalty points', 't').'<br />
			<strong>N</strong> - '.esc_html__('foresight factor. N = 0, if you guessed the score. N = 10, if you have not guessed the score, but guessed the side. N = 20, if you could not even guess the side', 't').'<br />
			<strong>X : Y</strong> - '.esc_html__('real score', 't').'<br />
			<strong>X1 : Y1</strong> - '.esc_html__('your bet', 't').'</p>
			<p>'.esc_html__('Example #1. Your bet for match Antarctica: Arctic - 1:2. Final score is - 3:0. In this case you get 24 penalty points: P = 20 + |3-1| + |0-2| = 24.', 't').'</p>
			<p>'.esc_html__('Example #2. Your bet for match Antarctica: Arctic - 2:0. Final score is - 1:1. In this case you get 22 penalty points: P = 20 + |1-2| + |1-0| = 22.', 't').'</p>
			<p>'.esc_html__('Example #3. Your bet for match Antarctica: Arctic - 1:0. Final score is - 2:0. In this case you get 11 penalty points: P = 10 + |2-1| + |0-0| = 11.', 't').'</p>
			
			<h2>'.esc_html__('How can I see the bets of other participants?', 't').'</h2>
			<p>'.esc_html__('There are 2 ways to do that:', 't').'</p>
			<ol>
				<li>'.esc_html__('Go to the desired totalizator and click "Participants" in the top menu. A list of participants will open. Click on any participant to view their bets on all matches.', 't').'</li>
				<li>'.esc_html__('Go to the desired totalizator and click "Games" in the top menu. A list of matches will open. Click on any match to see the bets of all participants on it.', 't').'</li>
			</ol>
			<p>'.esc_html__('Remember, you can see bets of other participants for completed matches only.', 't').'</p>
			
			<h2>'.esc_html__('Hmmm. The start of the match on the pages "My Bets" and "Games" is incorrect. Why?', 't').'</h2>
			<p>'.esc_html__('Make sure you have the correct time zone selected on the Settings page.', 't').'</p>
			
			<h2>'.esc_html__('Can I refuse to participate in the totalizator?', 't').'</h2>
			<p>'.esc_html__('Yes, but only before it starts. In the header of the site, click "Quit Totalizator.".', 't').'</p>
			
			<h2>'.esc_html__('Can I join the totalizator if it has already started?', 't').'</h2>
			<p>'.esc_html__('Yes, but for each missed match you will receive a fixed number of penalty points (depending on the totalizator settings, usually 26).', 't').'</p>
			
			<h2>'.esc_html__('Can I create my own totalizator?', 't').'</h2>
			<p>'.esc_html__('Yes, you can. Click the button "Create new totalizator" on the main page. Your totalizator will have the "Private" status.
			This status means that the totalizator will not appear in the list of public totalizators. Participants will be able to join it using a special link.
			After creating the totalizator, you are its administrator. You can create and edit matches.', 't').'</p>
		</div>';

		return array('title' => $title, 'content' => $content);
	}
}
$t = new t_class();
?>