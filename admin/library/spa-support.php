<?php
/*
  Simple:Press
  Admin Support Routines
  $LastChangedDate: 2018-08-15 07:59:04 -0500 (Wed, 15 Aug 2018) $
  $Rev: 15704 $
 */

if (preg_match('#'.basename(__FILE__).'#', $_SERVER['PHP_SELF'])) die('Access denied - you cannot directly call this file');

function spa_get_forums_in_group($groupid) {
	return SP()->DB->table(SPFORUMS, "group_id=$groupid", '', 'forum_seq');
}

function spa_get_group_forums_by_parent($groupid, $parentid) {
	return SP()->DB->table(SPFORUMS, "group_id=$groupid AND parent=$parentid", '', 'forum_seq');
}

function spa_get_forums_all() {
	return SP()->DB->select('SELECT forum_id, forum_name, forum_status, forum_disabled, '.SPGROUPS.'.group_id, group_name
		 FROM '.SPFORUMS.'
		 JOIN '.SPGROUPS.' ON '.SPFORUMS.'.group_id = '.SPGROUPS.'.group_id
		 ORDER BY group_seq, forum_seq');
}

function spa_create_group_select($groupid = 0, $label = false) {
	$groups  = SP()->DB->table(SPGROUPS, '', '', 'group_seq');
	$out     = '';
	$default = '';

	if ($groups) {
		if ($label) {
			$out .= '<option value="">'.SP()->primitives->admin_text('Select forum group:').'</option>';
		}
		foreach ($groups as $group) {
			if ($group->group_id == $groupid) {
				$default = 'selected="selected" ';
			} else {
				$default = null;
			}
			$out .= '<option '.$default.'value="'.$group->group_id.'">'.SP()->displayFilters->title($group->group_name).'</option>'."\n";
			$default = '';
		}
	}

	return $out;
}

function spa_create_forum_select($forumid) {
	$forums = spa_get_forums_all();
	$out    = '';
	if ($forums) {
		foreach ($forums as $forum) {
			if ($forum->forum_id == $forumid) {
				$default = 'selected="selected" ';
			} else {
				$default = '';
			}
			$out .= '<option '.$default.'value="'.$forum->forum_id.'">'.SP()->displayFilters->title($forum->forum_name).'</option>'."\n";
			$default = '';
		}
	}

	return $out;
}

function spa_update_check_option($key) {
	if (isset($_POST[$key])) {
		SP()->options->update($key, true);
	} else {
		SP()->options->update($key, false);
	}
}

function spa_get_usergroups_all($usergroupid = null) {
	$where = '';
	if (!is_null($usergroupid)) $where = "usergroup_id=$usergroupid";

	return SP()->DB->table(SPUSERGROUPS, $where);
}

function spa_get_usergroups_row($usergroup_id) {
	return SP()->DB->table(SPUSERGROUPS, "usergroup_id=$usergroup_id", 'row');
}

function spa_create_usergroup_row($usergroupname, $usergroupdesc, $usergroupbadge, $usergroupjoin, $hide_stats, $usergroupismod, $report_failure = false) {
	# first check to see if user group name exists
	$exists = SP()->DB->table(SPUSERGROUPS, "usergroup_name='$usergroupname'", 'usergroup_id');
	if ($exists) {
		if ($report_failure == true) {
			return false;
		} else {
			return $exists;
		}
	}

	# go on and create the new user group
	$sql = 'INSERT INTO '.SPUSERGROUPS.' (usergroup_name, usergroup_desc, usergroup_badge, usergroup_join, hide_stats, usergroup_is_moderator) ';
	$sql .= "VALUES ('$usergroupname', '$usergroupdesc', '$usergroupbadge', '$usergroupjoin', '$hide_stats', '$usergroupismod')";

	if (SP()->DB->execute($sql)) {
		return SP()->rewrites->pageData['insertid'];
	} else {
		return false;
	}
}

function spa_remove_permission_data($permission_id) {
	return SP()->DB->execute('DELETE FROM '.SPPERMISSIONS." WHERE permission_id=$permission_id");
}

function spa_create_role_row($role_name, $role_desc, $auths, $report_failure = false) {
	# first check to see if rolename exists
	$exists = SP()->DB->table(SPROLES, "role_name='$role_name'", 'role_id');
	if ($exists) {
		if ($report_failure == true) {
			return false;
		} else {
			return $exists;
		}
	}

	# go on and create the new role
	$sql = 'INSERT INTO '.SPROLES.' (role_name, role_desc, role_auths) ';
	$sql .= "VALUES ('$role_name', '$role_desc', '$auths')";

	if (SP()->DB->execute($sql)) {
		return SP()->rewrites->pageData['insertid'];
	} else {
		return false;
	}
}

function spa_get_role_row($role_id) {
	return SP()->DB->table(SPROLES, "role_id=$role_id", 'row');
}

function spa_get_defpermissions($group_id) {
	return SP()->DB->select('SELECT permission_id, '.SPUSERGROUPS.'.usergroup_id, permission_role, usergroup_name
		FROM '.SPDEFPERMISSIONS.'
		JOIN '.SPUSERGROUPS.' ON '.SPDEFPERMISSIONS.'.usergroup_id = '.SPUSERGROUPS.".usergroup_id
		WHERE group_id=$group_id");
}

function spa_get_defpermissions_role($group_id, $usergroup_id) {
	return SP()->DB->table(SPDEFPERMISSIONS, "group_id=$group_id AND usergroup_id=$usergroup_id", 'permission_role');
}

function spa_display_usergroup_select($filter = false, $forum_id = 0, $showSelect = true) {
	$usergroups = spa_get_usergroups_all();
	if ($showSelect) echo SP()->primitives->admin_text('Select usergroup').':&nbsp;&nbsp;';
	if ($showSelect) {
		?>
        <select style="width:145px" class='sfacontrol' name='usergroup_id'>
		<?php
	}
	$out = '<option value="-1">'.SP()->primitives->admin_text('Select usergroup').'</option>';
	if ($filter) $perms = sp_get_forum_permissions($forum_id);
	foreach ($usergroups as $usergroup) {
		$disabled = '';
		if ($filter == 1 && $perms) {
			foreach ($perms as $perm) {
				if ($perm->usergroup_id == $usergroup->usergroup_id) {
					$disabled = 'disabled="disabled" ';
					continue;
				}
			}
		}
		$out .= '<option '.$disabled.'value="'.$usergroup->usergroup_id.'">'.SP()->displayFilters->title($usergroup->usergroup_name).'</option>'."\n";
	}
	echo $out;
	if ($showSelect) {
		?>
        </select>
		<?php
	}
}

function spa_display_permission_select($cur_perm = 0, $showSelect = true) {
	?>
	<?php $roles = sp_get_all_roles(); ?>
	<?php if ($showSelect) { ?>
        <select style="width:165px" class='sfacontrol' name='role'>
		<?php
	}
	$out = '';
	if ($cur_perm == 0) $out .= '<option value="-1">'.SP()->primitives->admin_text('Select permission set').'</option>';
	foreach ($roles as $role) {
		$selected = '';
		if ($cur_perm == $role->role_id) $selected = 'selected = "selected" ';
		$out .= '<option '.$selected.'value="'.$role->role_id.'">'.SP()->displayFilters->title($role->role_name).'</option>'."\n";
	}
	echo $out;
	if ($showSelect) {
		?>
        </select>
		<?php
	}
}

function spa_select_icon_dropdown($name, $label, $path, $cur, $showSelect = true, $width = 0) {
	# Open folder and get cntents for matching
	$dlist = @opendir($path);
	if (!$dlist) return;

	$files = array();
	while (false !== ($file = readdir($dlist))) {
		if ($file != '.' && $file != '..') {
			$files[] = $file;
		}
	}
	closedir($dlist);
	if (empty($files)) return;
	sort($files);

	$w = '';
	if ($width > 0) $w = 'width:'.$width.'px;';
	if ($showSelect) echo '<select name="'.$name.'" class="sfcontrol" style="vertical-align:middle;'.$w.'">';
	if ($cur != '') $label = SP()->primitives->admin_text('Remove');
	echo '<option value="">'.$label.'</option>';

	foreach ($files as $file) {
		$selected = '';
		if ($file == $cur) $selected = ' selected="selected"';
		echo '<option'.$selected.' value="'.esc_attr($file).'">'.esc_html($file).'</option>';
	}
	if ($showSelect) echo '</select>';
}

# 5.2 add new auth categories for grouping of auths
# 6.0 updated for new instals only

function spa_setup_auth_cats() {
	# have the auths tables been created?
	$auths = SP()->DB->tableExists(SPAUTHS);

	# default auths
	SP()->auths->create_cat(SP()->primitives->admin_text('General'), SP()->primitives->admin_text('auth category for general auths'), 1);
	# viewing auths
	SP()->auths->create_cat(SP()->primitives->admin_text('Viewing'), SP()->primitives->admin_text('auth category for viewing auths'), 2);
	# creating auths
	SP()->auths->create_cat(SP()->primitives->admin_text('Creating'), SP()->primitives->admin_text('auth category for creating auths'), 3);
	# editing auths
	SP()->auths->create_cat(SP()->primitives->admin_text('Editing'), SP()->primitives->admin_text('auth category for editing auths'), 4);
	# deleting auths
	SP()->auths->create_cat(SP()->primitives->admin_text('Deleting'), SP()->primitives->admin_text('auth category for deleting auths'), 5);
	# moderation auths
	SP()->auths->create_cat(SP()->primitives->admin_text('Moderation'), SP()->primitives->admin_text('auth category for moderation auths'), 6);
	# tools auths
	SP()->auths->create_cat(SP()->primitives->admin_text('Tools'), SP()->primitives->admin_text('auth category for tools auths'), 7);
	# uploading auths
	SP()->auths->create_cat(SP()->primitives->admin_text('Uploading'), SP()->primitives->admin_text('auth category for uploading auths'), 8);
}

function spa_setup_auths() {
	# create the auths
	SP()->auths->add('view_forum', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view a forum')), 1, 0, 0, 0, 2, '');
	SP()->auths->add('view_forum_lists', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view a list of forums only')), 1, 0, 0, 0, 2, '');
	SP()->auths->add('view_forum_topic_lists', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view a list of forums and list of topics only')), 1, 0, 0, 0, 2, '');
	SP()->auths->add('view_admin_posts', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view posts by an administrator')), 1, 0, 0, 0, 2, '');
	SP()->auths->add('view_own_admin_posts', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view only own posts and admin/mod posts')), 1, 1, 0, 1, 2, '');
	SP()->auths->add('view_email', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view email and IP addresses of members')), 1, 1, 0, 0, 2, '');
	SP()->auths->add('view_profiles', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view profiles of members')), 1, 0, 0, 0, 2, '');
	SP()->auths->add('view_members_list', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view the members lists')), 1, 0, 0, 0, 2, '');
	SP()->auths->add('view_links', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view links within posts')), 1, 0, 0, 0, 2, '');
	SP()->auths->add('start_topics', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can start new topics in a forum')), 1, 0, 0, 0, 3, '');
	SP()->auths->add('reply_topics', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can reply to existing topics in a forum')), 1, 0, 0, 0, 3, '');
	SP()->auths->add('reply_own_topics', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can only reply to own topics')), 1, 1, 0, 1, 3, '');
	SP()->auths->add('bypass_flood_control', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can bypass wait time between posts')), 1, 0, 0, 0, 3, '');
	SP()->auths->add('use_spoilers', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can use spoilers in posts in posts')), 1, 0, 0, 0, 3, '');
	SP()->auths->add('use_signatures', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can attach a signature to posts')), 1, 1, 0, 0, 3, '');
	SP()->auths->add('create_links', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can create links in posts')), 1, 0, 0, 0, 3, '');
	SP()->auths->add('can_use_smileys', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can use smileys in posts')), 1, 0, 0, 0, 3, '');
	SP()->auths->add('can_use_iframes', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can use iframes in posts')), 1, 1, 0, 0, 3, SP()->primitives->admin_text('*** WARNING *** The use of iframes is dangerous. Allowing users to create iframes enables them to launch a potential security threat against your website. Enabling iframes requires your trust in your users. Turn on with care.'));
	SP()->auths->add('edit_own_topic_titles', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can edit own topic titles')), 1, 1, 0, 0, 4, '');
	SP()->auths->add('edit_any_topic_titles', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can edit any topic title')), 1, 1, 0, 0, 4, '');
	SP()->auths->add('edit_own_posts_for_time', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can edit own posts for time period')), 1, 1, 0, 0, 4, '');
	SP()->auths->add('edit_own_posts_forever', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can edit own posts forever')), 1, 1, 0, 0, 4, '');
	SP()->auths->add('edit_own_posts_reply', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can edit own posts until there has been a reply')), 1, 1, 0, 0, 4, '');
	SP()->auths->add('edit_any_post', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can edit any post')), 1, 1, 0, 0, 4, '');
	SP()->auths->add('delete_topics', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can delete topics in forum')), 1, 1, 0, 0, 5, '');
	SP()->auths->add('delete_own_posts', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can delete own posts')), 1, 1, 0, 0, 5, '');
	SP()->auths->add('delete_any_post', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can delete any post')), 1, 1, 0, 0, 5, '');
	SP()->auths->add('bypass_math_question', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can bypass the math question')), 1, 0, 0, 0, 6, '');
	SP()->auths->add('bypass_moderation', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can bypass all post moderation')), 1, 0, 0, 0, 6, '');
	SP()->auths->add('bypass_moderation_once', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can bypass first post moderation')), 1, 0, 0, 0, 6, '');
	SP()->auths->add('moderate_posts', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can moderate pending posts')), 1, 1, 0, 0, 6, '');
	SP()->auths->add('pin_topics', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can pin topics in a forum')), 1, 0, 0, 0, 7, '');
	SP()->auths->add('move_topics', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can move topics from a forum')), 1, 0, 0, 0, 7, '');
	SP()->auths->add('move_posts', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can move posts from a topic')), 1, 0, 0, 0, 7, '');
	SP()->auths->add('lock_topics', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can lock topics in a forum')), 1, 0, 0, 0, 7, '');
	SP()->auths->add('pin_posts', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can pin posts within a topic')), 1, 0, 0, 0, 7, '');
	SP()->auths->add('reassign_posts', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can reassign posts to a different user')), 1, 0, 0, 0, 7, '');
	SP()->auths->add('upload_avatars', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can upload avatars')), 1, 1, 1, 0, 8, '');
	SP()->auths->add('can_view_images', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view images in posts')), 1, 0, 0, 0, 2, '');
	SP()->auths->add('can_view_media', SP()->filters->esc_sql(SP()->primitives->admin_text_noesc('Can view media in posts')), 1, 0, 0, 0, 2, '');
}

function spa_setup_permissions() {
	# Create default role data

	$role_name   = 'No Access';
	$role_desc   = 'Permission with no access to any Forum features';
	$new_actions = 'a:40:{i:1;i:0;i:2;i:0;i:3;i:0;i:4;i:0;i:5;i:0;i:6;i:0;i:7;i:0;i:8;i:0;i:9;i:0;i:10;i:0;i:11;i:0;i:12;i:0;i:13;i:0;i:14;i:0;i:15;i:0;i:16;i:0;i:17;i:0;i:18;i:0;i:19;i:0;i:20;i:0;i:21;i:0;i:22;i:0;i:23;i:0;i:24;i:0;i:25;i:0;i:26;i:0;i:27;i:0;i:28;i:0;i:29;i:0;i:30;i:0;i:31;i:0;i:32;i:0;i:33;i:0;i:34;i:0;i:35;i:0;i:36;i:0;i:37;i:0;i:38;i:0;i:39;i:0;i:40;i:0;}';
	spa_create_role_row($role_name, $role_desc, $new_actions);

	$role_name   = 'Read Only Access';
	$role_desc   = 'Permission with access to only view the Forum';
	$new_actions = 'a:40:{i:1;i:1;i:2;i:0;i:3;i:0;i:4;i:1;i:5;i:0;i:6;i:0;i:7;i:0;i:8;i:0;i:9;i:1;i:10;i:0;i:11;i:0;i:12;i:0;i:13;i:0;i:14;i:1;i:15;i:0;i:16;i:0;i:17;i:0;i:18;i:0;i:19;i:0;i:20;i:0;i:21;i:0;i:22;i:0;i:23;i:0;i:24;i:0;i:25;i:0;i:26;i:0;i:27;i:0;i:28;i:0;i:29;i:0;i:30;i:0;i:31;i:0;i:32;i:0;i:33;i:0;i:34;i:0;i:35;i:0;i:36;i:0;i:37;i:0;i:38;i:0;i:39;i:1;i:40;i:1;}';
	spa_create_role_row($role_name, $role_desc, $new_actions);

	$role_name   = 'Limited Access';
	$role_desc   = 'Permission with access to reply and start topics but with limited features';
	$new_actions = 'a:40:{i:1;i:1;i:2;i:0;i:3;i:0;i:4;i:1;i:5;i:0;i:6;i:0;i:7;i:1;i:8;i:1;i:9;i:1;i:10;i:1;i:11;i:1;i:12;i:0;i:13;i:0;i:14;i:1;i:15;i:0;i:16;i:1;i:17;i:1;i:18;i:0;i:19;i:0;i:20;i:0;i:21;i:0;i:22;i:0;i:23;i:0;i:24;i:0;i:25;i:0;i:26;i:0;i:27;i:0;i:28;i:0;i:29;i:0;i:30;i:0;i:31;i:0;i:32;i:0;i:33;i:0;i:34;i:0;i:35;i:0;i:36;i:0;i:37;i:0;i:38;i:1;i:39;i:1;i:40;i:1;}';
	spa_create_role_row($role_name, $role_desc, $new_actions);

	$role_name   = 'Standard Access';
	$role_desc   = 'Permission with access to reply and start topics with advanced features such as signatures';
	$new_actions = 'a:40:{i:1;i:1;i:2;i:0;i:3;i:0;i:4;i:1;i:5;i:0;i:6;i:0;i:7;i:1;i:8;i:1;i:9;i:1;i:10;i:1;i:11;i:1;i:12;i:0;i:13;i:0;i:14;i:1;i:15;i:1;i:16;i:1;i:17;i:1;i:18;i:0;i:19;i:0;i:20;i:0;i:21;i:0;i:22;i:0;i:23;i:1;i:24;i:0;i:25;i:0;i:26;i:0;i:27;i:0;i:28;i:0;i:29;i:1;i:30;i:1;i:31;i:0;i:32;i:0;i:33;i:0;i:34;i:0;i:35;i:0;i:36;i:0;i:37;i:0;i:38;i:1;i:39;i:1;i:40;i:1;}';
	spa_create_role_row($role_name, $role_desc, $new_actions);

	$role_name   = 'Full Access';
	$role_desc   = 'Permission with Standard Access features and math question bypass';
	$new_actions = 'a:40:{i:1;i:1;i:2;i:0;i:3;i:0;i:4;i:1;i:5;i:0;i:6;i:0;i:7;i:1;i:8;i:1;i:9;i:1;i:10;i:1;i:11;i:1;i:12;i:0;i:13;i:1;i:14;i:1;i:15;i:1;i:16;i:1;i:17;i:1;i:18;i:0;i:19;i:1;i:20;i:0;i:21;i:0;i:22;i:1;i:23;i:1;i:24;i:0;i:25;i:0;i:26;i:0;i:27;i:0;i:28;i:1;i:29;i:1;i:30;i:1;i:31;i:0;i:32;i:0;i:33;i:0;i:34;i:0;i:35;i:0;i:36;i:0;i:37;i:0;i:38;i:1;i:39;i:1;i:40;i:1;}';
	spa_create_role_row($role_name, $role_desc, $new_actions);

	$role_name   = 'Moderator Access';
	$role_desc   = 'Permission with access to all Forum features';
	$new_actions = 'a:40:{i:1;i:1;i:2;i:0;i:3;i:0;i:4;i:1;i:5;i:0;i:6;i:1;i:7;i:1;i:8;i:1;i:9;i:1;i:10;i:1;i:11;i:1;i:12;i:0;i:13;i:1;i:14;i:1;i:15;i:1;i:16;i:1;i:17;i:1;i:18;i:0;i:19;i:1;i:20;i:1;i:21;i:0;i:22;i:1;i:23;i:1;i:24;i:1;i:25;i:1;i:26;i:0;i:27;i:1;i:28;i:1;i:29;i:1;i:30;i:1;i:31;i:1;i:32;i:1;i:33;i:1;i:34;i:1;i:35;i:1;i:36;i:1;i:37;i:1;i:38;i:1;i:39;i:1;i:40;i:1;}';
	spa_create_role_row($role_name, $role_desc, $new_actions);
}

# 5.0 set up stuff for new profile tabs

function spa_new_profile_setup() {
	# set up tabs and menus
	SP()->profile->add_tab('Profile');
	SP()->profile->add_menu('Profile', 'Overview', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-overview.php');
	SP()->profile->add_menu('Profile', 'Edit Profile', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-profile.php');
	SP()->profile->add_menu('Profile', 'Edit Identities', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-identities.php');
	SP()->profile->add_menu('Profile', 'Edit Avatar', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-avatar.php');
	SP()->profile->add_menu('Profile', 'Edit Signature', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-signature.php', 0, 1, 'use_signatures');
	SP()->profile->add_menu('Profile', 'Edit Photos', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-photos.php');
	SP()->profile->add_menu('Profile', 'Account Settings', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-account.php');

	SP()->profile->add_tab('Options');
	SP()->profile->add_menu('Options', 'Edit Global Options', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-global-options.php');
	SP()->profile->add_menu('Options', 'Edit Posting Options', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-posting-options.php');
	SP()->profile->add_menu('Options', 'Edit Display Options', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-display-options.php');

	SP()->profile->add_tab('Usergroups');
	SP()->profile->add_menu('Usergroups', 'Show Memberships', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-memberships.php');

	SP()->profile->add_tab('Permissions');
	SP()->profile->add_menu('Permissions', 'Show Permissions', SP_PLUGIN_DIR.'/forum/profile/forms/sp-form-permissions.php');

	# overview message
	$spProfile = SP()->options->get('sfprofile');
	if (empty($spProfile['sfprofiletext'])) {
		$spProfile['sfprofiletext'] = 'Welcome to the User Profile Overview Panel. From here you can view and update your profile and options as well as view your Usergroup Memberships and Permissions.';
		SP()->options->update('sfprofile', $spProfile);
	}
}

# 5.5.6

function sp_add_caps() {
	global $wp_roles;
	if (class_exists('WP_Roles') && !isset($wp_roles)) $wp_roles = new WP_Roles();

	$wp_roles->add_cap('administrator', 'SPF Manage Options', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Forums', false);
	$wp_roles->add_cap('administrator', 'SPF Manage User Groups', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Permissions', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Components', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Admins', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Users', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Profiles', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Toolbox', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Plugins', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Themes', false);
	$wp_roles->add_cap('administrator', 'SPF Manage Integration', false);
}

# 5.5.3 - get and display simple stats for admin items

function sp_display_item_stats($table, $key, $value, $label) {
	$c = SP()->DB->count($table, "$key = $value");
	echo '<span class = "spItemStat">'.$label.' <b>'.$c.'</b></span>';
}

function spa_build_forum_permalink_slugs() {
    # grab all the forums
	$query        = new stdClass();
	$query->type  = 'set';
	$query->table = SPFORUMS;
	$forums       = SP()->DB->select($query);

	if ($forums) {
		foreach ($forums as $forum) {
		    # get base slug for this forum
			$slugs     = array($forum->forum_slug);
			$parent_id = $forum->parent;

			# add in any ancestor forums
			while (!empty($parent_id)) {
				# get acncestor forum
				$query        = new stdClass();
				$query->table = SPFORUMS;
				$query->where = "forum_id=$parent_id";
				$query->type  = 'row';
				$parent       = SP()->DB->select($query);
				$parent_id    = $parent->parent;

				# add in the ancestor forum slug
				$slugs[] = $parent->forum_slug;
			}

			# update the forum permalink slug with all ancestors and its slug
			$query         = new stdClass;
			$query->table  = SP_PREFIX.'sfforums';
			$query->fields = array('permalink_slug');
			$slug          = implode('/', array_reverse($slugs));
			$query->data   = array($slug);
			$query->where  = "forum_id=$forum->forum_id";
			$result        = SP()->DB->update($query);
		}
	}
}