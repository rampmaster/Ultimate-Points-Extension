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

class points_transfer
{
	/** @var \dmzx\ultimatepoints\core\functions_points */
	protected $functions_points;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var string */
	protected $phpEx;

	/** @var string phpBB root path */
	protected $phpbb_root_path;

	/**
	* The database tables
	*
	* @var string
	*/
	protected $points_log_table;

	protected $points_config_table;

	protected $points_values_table;

	/**
	* Constructor
	*
	* @param \phpbb\template\template		 	$template
	* @param \phpbb\user						$user
	* @param \phpbb\db\driver\driver_interface	$db
	* @param \phpbb\request\request		 		$request
	* @param \phpbb\config\config				$config
	* @param \phpbb\controller\helper		 	$helper
	* @param									$phpEx
	* @param									$phpbb_root_path
	* @param string 							$points_log_table
	* @param string 							$points_config_table
	* @param string								$points_values_table
	*
	*/

	public function __construct(\dmzx\ultimatepoints\core\functions_points $functions_points, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\config\config $config, \phpbb\controller\helper $helper, $phpEx, $phpbb_root_path, $points_log_table, $points_config_table, $points_values_table)
	{
		$this->functions_points		= $functions_points;
		$this->auth					= $auth;
		$this->template 			= $template;
		$this->user 				= $user;
		$this->db 					= $db;
		$this->request 				= $request;
		$this->config 				= $config;
		$this->helper 				= $helper;
		$this->phpEx 				= $phpEx;
		$this->phpbb_root_path 		= $phpbb_root_path;
		$this->points_log_table 	= $points_log_table;
		$this->points_config_table 	= $points_config_table;
		$this->points_values_table	= $points_values_table;
	}

	var $u_action;

	function main($checked_user)
	{
		// Get all point config names and config values
		$sql = 'SELECT config_name, config_value
				FROM ' . $this->points_config_table;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$points_config[$row['config_name']] = $row['config_value'];
		}
		$this->db->sql_freeresult($result);

		// Grab transfer fee
		$sql = 'SELECT transfer_fee
				FROM ' . $this->points_values_table;
		$result = $this->db->sql_query($sql);
		$transfer_fee = $this->db->sql_fetchfield('transfer_fee');
		$this->db->sql_freeresult($result);

		// Grab the variables
		$message		= $this->request->variable('comment', '', true);
		$adm_points		= $this->request->variable('adm_points', false);
		$transfer_id	= $this->request->variable('i', 0);
		$post_id		= $this->request->variable('post_id', 0);

		add_form_key('transfer_points');

		// Check, if transferring is allowed
		if (!$points_config['transfer_enable'])
		{
			$message = $this->user->lang['TRANSFER_REASON_TRANSFER'] . '<br /><br /><a href="' . $this->helper->route('dmzx_ultimatepoints_controller') . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>';
			trigger_error($message);
		}

		// Add part to bar
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'transfer_user')),
			'FORUM_NAME'	=> sprintf($this->user->lang['TRANSFER_TITLE'], $this->config['points_name']),
		));

		$submit = (isset($_POST['submit'])) ? true : false;
		if ($submit)
		{
			if (!check_form_key('transfer_points'))
			{
				trigger_error('FORM_INVALID');
			}

			// Get variables for transferring
			$am 		=	round($this->request->variable('amount', 0.00),2);
			$comment	=	$this->request->variable('comment', '', true);

			// Check, if the sender has enough cash
			if ($this->user->data['user_points'] < $am)
			{
				$message = sprintf($this->user->lang['TRANSFER_REASON_MINPOINTS'], $this->config['points_name']) . '<br /><br /><a href="' . $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'transfer_user')) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>';
				trigger_error($message);
			}

			// Check, if the amount is 0 or below
			if ($am <= 0)
			{
				$message = sprintf($this->user->lang['TRANSFER_REASON_UNDERZERO'], $this->config['points_name']) . '<br /><br /><a href="' . $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'transfer_user')) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>';
				trigger_error($message);
			}

			// Check, if the user is trying to send to himself
			if ($this->user->data['user_id'] == $checked_user['user_id'])
			{
				$message = sprintf($this->user->lang['TRANSFER_REASON_YOURSELF'], $this->config['points_name']) . '<br /><br /><a href="' . $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'transfer_user')) . '">&laquo; ' . $this->user->lang['BACK_TO_PREV'] . '</a>';
				trigger_error($message);
			}

			// Add cash to receiver
			$amount = (100 - $transfer_fee) / 100 * $am; // Deduct the transfer fee
			$this->functions_points->add_points($checked_user['user_id'], $amount);

			// Remove cash from sender
			$this->functions_points->substract_points($this->user->data['user_id'], $am);

			// Get current time for logs
			$current_time = time();

			// Add transfer information to the log
			$text = utf8_normalize_nfc($message);

			$sql = 'INSERT INTO ' . $this->points_log_table . ' ' . $this->db->sql_build_array('INSERT', array(
				'point_send'	=> (int) $this->user->data['user_id'],
				'point_recv'	=> (int) $checked_user['user_id'],
				'point_amount'	=> $am,
				'point_sendold'	=> $this->user->data['user_points'] ,
				'point_recvold'	=> $checked_user['user_points'],
				'point_comment'	=> $text,
				'point_type'	=> '1',
				'point_date'	=> $current_time,
			));
			$this->db->sql_query($sql);

			// Send pm to user
			if (!$points_config['transfer_pm_enable'] == 0 && $checked_user['user_allow_pm'] == 1)
			{
				// Select the user data for the PM
				$sql_array = array(
					'SELECT'	=> '*',
					'FROM'		=> array(
						USERS_TABLE => 'u',
					),
					'WHERE'		=> 'user_id = ' . (int) $checked_user['user_id'],
				);
				$sql = $this->db->sql_build_query('SELECT', $sql_array);
				$result = $this->db->sql_query($sql);
				$user_row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				$points_name 	= $this->config['points_name'];
				$comment 		= $this->db->sql_escape($comment);
				$pm_subject		= utf8_normalize_nfc(sprintf($this->user->lang['TRANSFER_PM_SUBJECT']));
				$pm_text		= utf8_normalize_nfc(sprintf($this->user->lang['TRANSFER_PM_BODY'], $amount, $points_name, $text));

				$poll = $uid = $bitfield = $options = '';
				generate_text_for_storage($pm_subject, $uid, $bitfield, $options, false, false, false);
				generate_text_for_storage($pm_text, $uid, $bitfield, $options, true, true, true);

				$pm_data = array(
					'address_list'		=> array ('u' => array($checked_user['user_id'] => 'to')),
					'from_user_id'		=> $this->user->data['user_id'],
					'from_username'		=> $this->user->data['username'],
					'icon_id'			=> 0,
					'from_user_ip'		=> '',

					'enable_bbcode'		=> true,
					'enable_smilies'	=> true,
					'enable_urls'		=> true,
					'enable_sig'		=> true,

					'message'			=> $pm_text,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_uid'		=> $uid,
				);

				submit_pm('post', $pm_subject, $pm_data, false);
			}

			$message = sprintf($this->user->lang['TRANSFER_REASON_TRANSUCC'], $this->functions_points->number_format_points($am), $this->config['points_name'], $checked_user['username']) . '<br /><br />' . (($post_id) ? sprintf($this->user->lang['EDIT_P_RETURN_POST'], '<a href="' . append_sid("{$this->phpbb_root_path}viewtopic.{$this->phpEx}", "p=" . $post_id) . '">', '</a>') : sprintf($this->user->lang['EDIT_P_RETURN_INDEX'], '<a href="' . append_sid("{$this->phpbb_root_path}index.{$this->phpEx}") . '">', '</a>'));
			trigger_error($message);

			$this->template->assign_vars(array(
				'U_ACTION'					=> $this->u_action,
			));
		}

		$username_full = get_username_string('full', $checked_user['user_id'], $checked_user['username'], $checked_user['user_colour']);

		$this->template->assign_vars(array(
			'L_TRANSFER_DESCRIPTION'		=> sprintf($this->user->lang['TRANSFER_DESCRIPTION'], $this->config['points_name']),
			'POINTS_NAME'					=> $this->config['points_name'],
			'POINTS_COMMENTS'				=> ($points_config['comments_enable']) ? true : false,
			'TRANSFER_FEE'					=> $transfer_fee,
			'U_TRANSFER_NAME'				=> sprintf($this->user->lang['TRANSFER_TO_NAME'], $username_full, $this->config['points_name']),

			'S_ALLOW_SEND_PM'				=> $this->auth->acl_get('u_sendpm'),
		));

		// Generate the page
		page_header(sprintf($this->user->lang['TRANSFER_TITLE'], $this->config['points_name']));

		// Generate the page template
		$this->template->set_filenames(array(
			'body' => 'points/points_transfer.html',
		));

		page_footer();
	}
}
