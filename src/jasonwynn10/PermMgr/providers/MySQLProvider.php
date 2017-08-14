<?php
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
		$this->db->query("CREATE TABLE IF NOT EXISTS players(id INT(16) PRIMARY KEY NOT NULL AUTO_INCREMENT, username VARCHAR(16) UNIQUE KEY NOT NULL, group VARCHAR(32) NOT NULL, permissions TEXT NOT NULL);");
	}

	/**
	 * @param IPlayer $player
	 */
	public function init(IPlayer $player) {
		$result = $this->db->query("INSERT INTO players(username, group) VALUES ('{$this->db->real_escape_string($player->getName())}', '{$this->plugin->getGroups()->getDefaultGroup()}');");
		if($result instanceof \mysqli_result) {
			return;
		}else{
			$this->plugin->getLogger()->error("Player {$player->getName()} could not be initialized!");
		}
	}

	/**
	 * @param IPlayer $player
	 *
	 * @throws \BadMethodCallException
	 * @return Config
	 */
	public function getPlayerConfig(IPlayer $player) : Config {
		throw new \BadMethodCallException("mysql doesn't have a Config!");
	}

	public function getGroup(IPlayer $player) : string {
		$result = $this->db->query("SELECT * FROM players WHERE username = '{$this->db->real_escape_string($player->getName())}';");
		if($result instanceof \mysqli_result) {
			$arr = $result->fetch_assoc();
			return $arr["group"];
		}else{
			return $this->plugin->getGroups()->getDefaultGroup();
		}

	}

	public function getPlayerPermissions(IPlayer $player) : array {
		$result = $this->db->query("SELECT * FROM players WHERE username = '{$this->db->real_escape_string($player->getName())}';");
		if($result instanceof \mysqli_result) {
			$arr = $result->fetch_assoc();
			$return = explode(", ", $arr["permissions"]);
			return $return;
		}else{
			return [];
		}
	}

	public function setPlayerPermissions(IPlayer $player, array $permissions) : bool {
		$permissions = implode(", ", $permissions);
		$result = $this->db->query("INSERT INTO players(userName, group, permissions) VALUES ('" . $this->db->escape_string($player->getName()) . "', '" . $this->db->escape_string($player->getName()) . "', '" . $this->db->escape_string($permissions) . "') ON DUPLICATE KEY UPDATE group = VALUES(group), permissions = VALUES(permissions);");
		if($result instanceof \mysqli_result) {
			// TODO
			return true;
		}else{
			return false;
		}
	}
}