<?php defined('BASEPATH') or exit('No direct script access allowed');

class Module_Polls extends Module {

	public $version = '0.8';

	public function info()
	{
		return array(
			'name' => array(
				'en' => 'Polls'
			),
			'description' => array(
				'en' => 'Create totally awesome polls.'
			),
			'frontend' => TRUE,
			'backend' => TRUE,
			'menu' => 'content',
			'shortcuts' => array(
				array(
			 	   'name' => 'polls.new_poll_label',
				   'uri' => 'admin/polls/create',
				),
			),
		);
	}

	public function install()
	{
		// Start transaction
		$this->db->trans_start();

		// Create polls table
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('polls') . "` (
			`id` tinyint(11) unsigned NOT NULL AUTO_INCREMENT,
			`slug` varchar(64) NOT NULL,
			`title` varchar(64) NOT NULL,
			`type` enum('single','multiple') NOT NULL DEFAULT 'single',
			`description` text,
			`open_date` int(16) unsigned DEFAULT NULL,
			`close_date` int(16) unsigned DEFAULT NULL,
			`created` int(16) unsigned NOT NULL,
			`last_updated` int(16) unsigned DEFAULT NULL,
			`multiple_votes` tinyint(1) unsigned NOT NULL DEFAULT '0',
			`comments_enabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
			`members_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB;
		");

		// Create poll_options table
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('poll_options') . "` (
			`id` smallint(11) unsigned NOT NULL AUTO_INCREMENT,
			`poll_id` tinyint(11) unsigned NOT NULL,
			`type` enum('defined','other') NOT NULL DEFAULT 'defined',
			`title` varchar(64) NOT NULL,
			`order` tinyint(2) unsigned DEFAULT NULL,
			`votes` mediumint(11) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`),
			KEY `poll_id` (`poll_id`)
			) ENGINE=InnoDB;
		");

		// Create poll_other_votes table
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('poll_other_votes') . "` (
			`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			`parent_id` smallint(11) unsigned NOT NULL,
			`text` tinytext NOT NULL,
			`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY `parent_id` (`parent_id`)
			) ENGINE=InnoDB;
		");

		// Create poll_voters table
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('poll_voters') . "` (
			`id` mediumint(32) unsigned NOT NULL AUTO_INCREMENT,
			`poll_id` tinyint(11) unsigned NOT NULL,
			`user_id` smallint(5) unsigned DEFAULT NULL,
			`session_id` varchar(40) NOT NULL,
			`ip_address` varchar(16) NOT NULL,
			`timestamp` int(11) unsigned NOT NULL,
			PRIMARY KEY (`id`),
			KEY `poll_id` (`poll_id`),
			KEY `user_id` (`user_id`)
			) ENGINE=InnoDB;
		");

		// Referental integrity fo' sho
		$this->db->query("
			ALTER TABLE `" . $this->db->dbprefix('poll_options') . "`
			ADD CONSTRAINT `poll_options_ibfk_1`
			FOREIGN KEY (`poll_id`)
			REFERENCES `" . $this->db->dbprefix('polls') . "` (`id`)
			ON DELETE CASCADE
			ON UPDATE CASCADE;
		");

		$this->db->query("
			ALTER TABLE `" . $this->db->dbprefix('poll_other_votes') . "`
			ADD CONSTRAINT `poll_other_votes_ibfk_1`
			FOREIGN KEY (`parent_id`)
			REFERENCES `" . $this->db->dbprefix('poll_options') . "` (`id`)
			ON DELETE CASCADE
			ON UPDATE CASCADE;
		");

		$this->db->query("
			ALTER TABLE `" . $this->db->dbprefix('poll_voters') . "`
			ADD CONSTRAINT `poll_votes_ibfk_1`
			FOREIGN KEY (`poll_id`)
			REFERENCES `" . $this->db->dbprefix('polls') . "` (`id`)
			ON DELETE CASCADE
			ON UPDATE CASCADE;
		");

		// End transaction
		$this->db->trans_complete();

		// If transaction was successful retrun TRUE, else FALSE
		return $this->db->trans_status() ? TRUE : FALSE;
	}

	public function uninstall()
	{
		// Drop some tables
		$this->db->query("DROP TABLE `" . $this->db->dbprefix('poll_voters') . "`, `" . $this->db->dbprefix('poll_other_votes') . "`, `" . $this->db->dbprefix('poll_options') . "`, `" . $this->db->dbprefix('polls') . "`");
		
		return TRUE;
	}

	public function upgrade($old_version)
	{
		// Start transaction
		$this->db->trans_start();

		// Version 0.4 (the first official release)
		if ($old_version == '0.4')
		{
			// Update polls table
			$this->db->query("
				ALTER TABLE  `polls` ADD  `type` enum('single','multiple') NOT NULL DEFAULT 'single'
			");

			// Update poll_options table
			$this->db->query("
				ALTER TABLE  `poll_options` ADD  `type` enum('defined','other') NOT NULL DEFAULT 'defined'
			");

			// Add poll_other_votes table
			$this->db->query("
				CREATE TABLE IF NOT EXISTS `poll_other_votes` (
				`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
				`parent_id` smallint(11) unsigned NOT NULL,
				`text` tinytext NOT NULL,
				`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `parent_id` (`parent_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
			");

			// Add poll_voters table
			$this->db->query("
				CREATE TABLE IF NOT EXISTS `poll_voters` (
				`id` mediumint(32) unsigned NOT NULL AUTO_INCREMENT,
				`poll_id` tinyint(11) unsigned NOT NULL,
				`user_id` smallint(5) unsigned DEFAULT NULL,
				`session_id` varchar(40) NOT NULL,
				`ip_address` varchar(16) NOT NULL,
				`timestamp` int(11) unsigned NOT NULL,
				PRIMARY KEY (`id`),
				KEY `poll_id` (`poll_id`),
				KEY `user_id` (`user_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
			");

			// Referental integrity fo' sho
			$this->db->query("
				ALTER TABLE `poll_other_votes`
				ADD CONSTRAINT `poll_other_votes_ibfk_1`
				FOREIGN KEY (`parent_id`)
				REFERENCES `poll_options` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE;
			");

			$this->db->query("
				ALTER TABLE `poll_voters`
				ADD CONSTRAINT `poll_votes_ibfk_1`
				FOREIGN KEY (`poll_id`)
				REFERENCES `polls` (`id`)
				ON DELETE CASCADE
				ON UPDATE CASCADE;
			");
		}
		// Version 0.5
		elseif ($old_version == '0.5')
		{
			$this->db->query("
				ALTER TABLE  `polls` ADD  `multiple_votes` TINYINT(1) NOT NULL DEFAULT  '0' AFTER  `last_updated`
			");
		}


		// If less than version 0.8
		if ($old_version < '0.8')
		{
			// Rename all tables to add prefix
			$this->db->query("RENAME TABLE  `polls` TO  `" . $this->db->dbprefix('polls') . "`");
			$this->db->query("RENAME TABLE  `poll_options` TO  `" . $this->db->dbprefix('poll_options') . "`");
			$this->db->query("RENAME TABLE  `poll_other_votes` TO  `" . $this->db->dbprefix('poll_other_votes') . "`");
			$this->db->query("RENAME TABLE  `poll_voters` TO  `" . $this->db->dbprefix('poll_voters') . "`");
			$this->db->query("RENAME TABLE  `poll_options` TO  `" . $this->db->dbprefix('poll_options') . "`");
			$this->db->query("RENAME TABLE  `poll_other_votes` TO  `" . $this->db->dbprefix('poll_other_votes') . "`");
			$this->db->query("RENAME TABLE  `poll_voters` TO  `" . $this->db->dbprefix('poll_voters') . "`");

		}

		// End transaction
		$this->db->trans_complete();

		// If transaction was successful retrun TRUE, else FALSE
		return $this->db->trans_status() ? TRUE : FALSE;
	}

	public function help()
	{
		return '<a href="https://github.com/vmichnowicz/polls">View Source on Github</a>';
	}
}
/* End of file details.php */