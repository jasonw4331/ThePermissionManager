<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\providers;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\IPlayer;
use pocketmine\utils\Config;

class SQLite3Provider extends DataProvider {
	/** @var \SQLite3 $db */
	private $db;
	/** @var \SQLite3Stmt  */
	private $sqlGetPermissions, $sqlGetGroupPlayers, $sqlSetGroups, $sqlInitPlayer, $sqlSetPermissions, $sqlGetGroup;

	/**
	 * SQLite3Provider constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin);
		$this->db = new \SQLite3($plugin->getDataFolder()."players.db");
		$this->db->exec(
			"CREATE TABLE IF NOT EXISTS players
			(username TEXT PRIMARY KEY, groups TEXT NOT NULL, permissions TEXT);"
		);
		$this->sqlInitPlayer = $this->db->prepare(
			"INSERT INTO players (username, groups, permissions) VALUES (:name, :groups, :perms);"
		);
		$this->sqlGetGroupPlayers = $this->db->prepare(
			"SELECT groups FROM players WHERE username = :name;" // TODO: statement needs to return all groups of a player
		);
		$this->sqlGetGroup = $this->db->prepare(
			";"
		);
		$this->sqlSetGroups = $this->db->prepare(
			"UPDATE players SET groups = :groups WHERE username = :name;"
		);
		$this->sqlGetPermissions = $this->db->prepare(
			"SELECT permissions FROM players WHERE username = :name;"
		);
		$this->sqlSetPermissions = $this->db->prepare(
			";"
		);
		$this->plugin->getLogger()->debug("SQLite data provider registered");
	}

	/**
	 * @param IPlayer $player
	 */
	public function init(IPlayer $player) : void {
		$stmt = $this->sqlInitPlayer;
		$stmt->bindValue(":name", $player->getName(), SQLITE3_TEXT);
		$result = $stmt->execute();
		if ($result === false) {
			$this->plugin->getLogger()->debug("There was an error initializing a player in SQLite!");
		}
	}

	/**
	 * @param IPlayer $player
	 * @param string $levelName
	 *
	 * @return array
	 */
	public function getPlayerPermissions(IPlayer $player, string $levelName = "") : array {
		$stmt = $this->sqlGetPermissions;
		$stmt->bindValue(":name", $player->getName(), SQLITE3_TEXT);
		$result = $stmt->execute();
		if ($result === false) {
			$this->plugin->getLogger()->debug("There was an error getting a player's permissions in SQLite!");
		}
		var_dump($val = $result->fetchArray(SQLITE3_ASSOC)); // TODO: remove debug
		$perms = explode(",", $val["permissions"]);
		var_dump($perms); // TODO: remove debug
		return $perms;
	}

	/**
	 * @return array
	 */
	public function getGroupPlayers() : array { // TODO: finish algorithm
		$stmt = $this->sqlGetGroupPlayers;

		foreach ($this->plugin->getGroups()->getGroupsConfig()->getAll(true) as $group) {

		}
	}

	/**
	 * @param IPlayer $player
	 * @param array $permissions
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function setPlayerPermissions(IPlayer $player, array $permissions, string $levelName = "") : bool {
		$stmt = $this->sqlSetPermissions;
		$stmt->bindValue(":perms", implode(", ", $permissions), SQLITE3_TEXT);
		$result = $stmt->execute();
		if ($result === false) {
			$this->plugin->getLogger()->debug("There was an error setting a player's permissions in SQLite!");
			return false;
		}
		return true;
	}

	/**
	 * @param IPlayer $player
	 *
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
		$stmt = $this->sqlGetGroup;
		$stmt->bindValue(":name", $player->getName(), SQLITE3_TEXT);
		$result = $stmt->execute();
		if ($result === false) {
			$this->plugin->getLogger()->debug("There was an error setting a player's permissions in SQLite!");
		}
		$var = $result->fetchArray(SQLITE3_ASSOC);
		return explode(", ", $var["groups"]);
	}

	/**
	 * @param IPlayer $player
	 * @param array $groups
	 *
	 * @return bool
	 */
	public function setGroups(IPlayer $player, array $groups) : bool {
		$stmt = $this->sqlSetGroups;
		$stmt->bindValue();
		//TODO: finish
	}
}