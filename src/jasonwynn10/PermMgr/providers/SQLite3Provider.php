<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\providers;

use jasonwynn10\PermMgr\ThePermissionManager;
use pocketmine\IPlayer;

class SQLite3Provider extends DataProvider {
	/** @var \SQLite3 $db */
	private $db;
	/** @var \SQLite3Stmt  */
	private $sqlGetPlayer, $sqlGetPermissions;

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
			(player TEXT PRIMARY KEY, permissions TEXT);"
		);
		$this->sqlGetPlayer = $this->db->prepare(
			"SELECT player FROM players WHERE permissions = :permissions;"
		);
		$this->sqlGetPermissions = $this->db->prepare(
			"SELECT permissions FROM players WHERE player = :player;"
		);
		$this->plugin->getLogger()->debug("SQLite data provider registered");
	}

	/**
	 * @param IPlayer $player
	 */
	public function init(IPlayer $player) : void {
		// TODO: Implement init() method.
	}
}