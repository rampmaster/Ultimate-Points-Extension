<?php
/**
*
* @package phpBB Extension - Ultimate Points
* @copyright (c) 2015 dmzx & posey - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\ultimatepoints\core;

/**
* @package Ultimate Points
*/

class points_info
{
	/** @var \dmzx\ultimatepoints\core\functions_points */
	protected $functions_points;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var string phpBB root path */
	protected $phpbb_root_path;

	protected $points_values_table;

	/**
	* Constructor
	*
	* @param \dmzx\ultimatepoints\core\functions_points
	* @param \phpbb\auth\auth					$auth
	* @param \phpbb\db\driver\driver_interface	$db
	* @param \phpbb\template\template		 	$template
	* @param \phpbb\user						$user
	* @param \phpbb\config\config				$config
	* @param \phpbb\controller\helper		 	$helper
	* @param									$phpbb_root_path
	*
	*/

	public function __construct(\dmzx\ultimatepoints\core\functions_points $functions_points, \phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, \phpbb\config\config $config, \phpbb\controller\helper $helper, $phpbb_root_path, $points_values_table)
	{
		$this->functions_points		= $functions_points;
		$this->auth					= $auth;
		$this->db					= $db;
		$this->template 			= $template;
		$this->user 				= $user;
		$this->config 				= $config;
		$this->helper 				= $helper;
		$this->phpbb_root_path		= $phpbb_root_path;
		$this->points_values_table	= $points_values_table;
	}

	var $u_action;

	function main()
	{
		$sql = 'SELECT *
				FROM '. $this->points_values_table;
		$result = $this->db->sql_query($sql);
		$points_values = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Add part to bar
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'info')),
			'FORUM_NAME'	=> sprintf($this->user->lang['POINTS_INFO'], $this->config['points_name']),
		));

		// Read out all the need values
		$info_attach 			= ($points_values['points_per_attach'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) :	sprintf($this->functions_points->number_format_points($points_values['points_per_attach']) . '&nbsp;' . $this->config['points_name']);
		$info_addtional_attach	= ($points_values['points_per_attach_file'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) : sprintf($this->functions_points->number_format_points($points_values['points_per_attach_file']) . '&nbsp;' . $this->config['points_name']);
		$info_poll				= ($points_values['points_per_poll'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) : sprintf($this->functions_points->number_format_points($points_values['points_per_poll']) . '&nbsp;' . $this->config['points_name']);
		$info_poll_option		= ($points_values['points_per_poll_option'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) : sprintf($this->functions_points->number_format_points($points_values['points_per_poll_option']) . '&nbsp;' . $this->config['points_name']);
		$info_topic_word		= ($points_values['points_per_topic_word'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) : sprintf($this->functions_points->number_format_points($points_values['points_per_topic_word']) . '&nbsp;' . $this->config['points_name']);
		$info_topic_character	= ($points_values['points_per_topic_character'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) : sprintf($this->functions_points->number_format_points($points_values['points_per_topic_character']) . '&nbsp;' . $this->config['points_name']);
		$info_post_word			= ($points_values['points_per_post_word'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) : sprintf($this->functions_points->number_format_points($points_values['points_per_post_word']) . '&nbsp;' . $this->config['points_name']);
		$info_post_character	= ($points_values['points_per_post_character'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) : sprintf($this->functions_points->number_format_points($points_values['points_per_post_character']) . '&nbsp;' . $this->config['points_name']);
		$info_cost_warning		= ($points_values['points_per_warn'] == 0) ? sprintf($this->user->lang['INFO_NO_COST'], $this->config['points_name']) : sprintf($this->functions_points->number_format_points($points_values['points_per_warn']) . '&nbsp;' . $this->config['points_name']);
		$info_reg_bonus			= ($points_values['reg_points_bonus'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) : sprintf($this->functions_points->number_format_points($points_values['reg_points_bonus']) . '&nbsp;' . $this->config['points_name']);
		$info_points_bonus		= ($points_values['points_bonus_chance'] == 0) ? sprintf($this->user->lang['INFO_NO_POINTS'], $this->config['points_name']) : sprintf($this->user->lang['INFO_BONUS_CHANCE_EXPLAIN'], $this->functions_points->number_format_points($points_values['points_bonus_chance']), $this->functions_points->number_format_points($points_values['points_bonus_min']), $this->functions_points->number_format_points($points_values['points_bonus_max']), $this->config['points_name']);

		$this->template->assign_vars(array(
			'USER_POINTS'				=> sprintf($this->functions_points->number_format_points($this->user->data['user_points'])),
			'POINTS_NAME'				=> $this->config['points_name'],
			'LOTTERY_NAME'				=> $points_values['lottery_name'],
			'BANK_NAME'					=> $points_values['bank_name'],
			'POINTS_INFO_DESCRIPTION'	=> sprintf($this->user->lang['POINTS_INFO_DESCRIPTION'], $this->config['points_name']),

			'INFO_ATTACH'				=> $info_attach,
			'INFO_ADD_ATTACH'			=> $info_addtional_attach,
			'INFO_POLL'					=> $info_poll,
			'INFO_POLL_OPTION'			=> $info_poll_option,
			'INFO_TOPIC_WORD'			=> $info_topic_word,
			'INFO_TOPIC_CHARACTER'		=> $info_topic_character,
			'INFO_POST_WORD'			=> $info_post_word,
			'INFO_POST_CHARACTER'		=> $info_post_character,
			'INFO_COST_WARNING'			=> $info_cost_warning,
			'INFO_REG_BONUS'			=> $info_reg_bonus,
			'INFO_POINTS_BONUS'			=> $info_points_bonus,

			'U_TRANSFER_USER'			=> $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'transfer_user')),
			'U_LOGS'					=> $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'logs')),
			'U_LOTTERY'					=> $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'lottery')),
			'U_BANK'					=> $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'bank')),
			'U_ROBBERY'					=> $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'robbery')),
			'U_INFO'					=> $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'info')),
			'U_USE_TRANSFER'			=> $this->auth->acl_get('u_use_transfer'),
			'U_USE_LOGS'				=> $this->auth->acl_get('u_use_logs'),
			'U_USE_LOTTERY'				=> $this->auth->acl_get('u_use_lottery'),
			'U_USE_BANK'				=> $this->auth->acl_get('u_use_bank'),
			'U_USE_ROBBERY'				=> $this->auth->acl_get('u_use_robbery'),
		));

		// Generate the page
		page_header($this->user->lang['POINTS_INFO']);

		// Generate the page template
		$this->template->set_filenames(array(
			'body'	=> 'points/points_info.html'
		));

		page_footer();
	}
}
