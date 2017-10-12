<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\providers;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\IPlayer;
use pocketmine\utils\Config;

class MySQLProvider extends DataProvider {
	/** @var \mysqli $db */
	private $db;

	/**
	 * MySQLProvider constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin);

		$this->db = new \mysqli(
			$plugin->getConfig()->getNested("mysql-settings.host"),
			$plugin->getConfig()->getNested("mysql-settings.user", "root"),
			$plugin->getConfig()->getNested("mysql-settings.password", "password"),
			$plugin->getConfig()->getNested("mysql-settings.db", "PermissionsDB"),
			$plugin->getConfig()->getNested("mysql-settings.port", 3306)
		);
		$this->db->query("CREATE TABLE IF NOT EXISTS players(id INT(16) PRIMARY KEY NOT NULL AUTO_INCREMENT, username VARCHAR(16) UNIQUE KEY NOT NULL, permissions TEXT NOT NULL);");
	}

	/**
	 * @param IPlayer $player
	 */
	public function init(IPlayer $player) : void {
		$groups = implode(", ", $this->plugin->getGroups()->getDefaultGroups());
		$result = $this->db->query("INSERT INTO players(username, group) VALUES ('{$this->db->real_escape_string($player->getName())}', '{$groups}');");
		if($result instanceof \mysqli_result) {
			return;
		}else{
			$this->plugin->getLogger()->error("Player {$player->getName()} could not be initialized!");
		}
	}

	/**
	 * @param IPlayer $player
	 * @param string $levelName
	 *
	 * @return array
	 */
	public function getPlayerPermissions(IPlayer $player, string $levelName = "") : array {
		$result = $this->db->query("SELECT * FROM players WHERE username = '{$this->db->real_escape_string($player->getName())}';");
		if($result instanceof \mysqli_result) {
			$arr = $result->fetch_assoc();
			$return = explode(", ", $arr["permissions"]);
			return $return;
		}else{
			return [];
		}
	}

	/**
	 * @return array
	 */
	public function getPlayerGroups(): array {
		// TODO: Implement getPlayerGroups() method.
	}

	/**
	 * @param IPlayer $player
	 * @param array $permissions
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function setPlayerPermissions(IPlayer $player, array $permissions, string $levelName = "") : bool {
		$permissions = implode(", ", $permissions);
		$result = $this->db->query("INSERT INTO players(username, group, permissions) VALUES ('" . $this->db->escape_string($player->getName()) . "', '" . $this->db->escape_string($this->getGroup($player)) . "', '" . $this->db->escape_string($permissions) . "') ON DUPLICATE KEY UPDATE group = VALUES(group), permissions = VALUES(permissions);");
		if($result instanceof \mysqli_result) {
			// TODO
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @param IPlayer $player
	 *
	 * @throws \BadMethodCallException
	 * @return Config
	 */
	public function getPlayerConfig(IPlayer $player) : Config {
		throw new \BadMethodCallException("SQL doesn't have a Config!");
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return array
	 */
	public function getGroups(IPlayer $player) : array {
		$result = $this->db->query("SELECT * FROM players WHERE username = '{$this->db->real_escape_string($player->getName())}';");
		if($result instanceof \mysqli_result) {
			$arr = $result->fetch_assoc();
			return explode(", ", $arr["group"]);
		}else{
			return $this->plugin->getGroups()->getDefaultGroups();
		}

	}

	/**
	 * @param IPlayer $player
	 * @param array $groups
	 *
	 * @return bool
	 */
	public function setGroups(IPlayer $player, array $groups) : bool {
		$result = $this->db->query("INSERT INTO players(group) WHERE username='{$this->db->real_escape_string($player->getName())}' ON DUPLICATE KEY UPDATE group = VALUES(group);");
		if($result instanceof \mysqli_result) {
			// TODO
			return true;
		}else{
			// TODO
			return false;
		}
	}
}