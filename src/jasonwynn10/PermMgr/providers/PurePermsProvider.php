<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\providers;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\IPlayer;
use pocketmine\utils\Config;

class PurePermsProvider extends DataProvider {
	/**
	 * PurePermsProvider constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin);
		new Config($this->plugin->getDataFolder() . "players.yml", Config::YAML, [
			"jasonwynn10" => [
				"group" => implode(", ", $this->plugin->getGroups()->getDefaultGroups()),
				"permissions" => [],
				"worlds" => [],
				"time" => -1
			]
		]);
	}

	/**
	 * @param IPlayer $player
	 */
	public function init(IPlayer $player) : void {
		$userName = strtolower($player->getName());
		if(!$this->getPlayerConfig($player)->exists($userName)) {
			$this->getPlayerConfig($player)->set($userName, [
				"group" => implode(", ", $this->plugin->getGroups()->getDefaultGroups()),
				"permissions" => [],
				"worlds" => [],
				"time" => -1
			]);
		}
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return Config
	 */
	public function getPlayerConfig(IPlayer $player = null) : Config {
		return new Config($this->plugin->getDataFolder() . "players.yml", Config::YAML);
	}

	/**
	 * @param IPlayer $player
	 * @param string $levelName
	 *
	 * @return array
	 */
	public function getPlayerPermissions(IPlayer $player, string $levelName = "") : array {
		if(empty($levelName)) {
			return $this->getPlayerConfig($player)->getNested(strtolower($player->getName()).".permissions", []);
		}else{
			return $this->getPlayerConfig($player)->getNested(strtolower($player->getName()).".worlds.$levelName", []);
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
		if(empty($levelName)) {
			$config = $this->getPlayerConfig($player);
			$config->setNested(strtolower($player->getName()).".permissions", $permissions);
			return $config->save(true);
		}else{
			$config = $this->getPlayerConfig($player);
			$config->setNested(strtolower($player->getName()).".worlds.$levelName", $permissions);
			return $config->save(true);
		}
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return array
	 */
	public function getGroups(IPlayer $player) : array {
		return is_array($this->getPlayerConfig($player)->getNested(strtolower($player->getName()).".group", $this->plugin->getGroups()->getDefaultGroups())) ? [$this->getPlayerConfig($player)->getNested(strtolower($player->getName()).".group", $this->plugin->getGroups()->getDefaultGroups()[0])] : $this->getPlayerConfig($player)->getNested(strtolower($player->getName()).".group", [$this->plugin->getGroups()->getDefaultGroups()]);
	}

	/**
	 * @param IPlayer $player
	 * @param array $groups
	 *
	 * @return bool
	 */
	public function setGroups(IPlayer $player, array $groups) : bool {
		$config = $this->getPlayerConfig($player);
		$config->setNested(strtolower($player->getName()).".group", $groups);
		return $config->save(true);
	}

	/**
	 * @return array
	 */
	public function getGroupPlayers() : array {
		$return = [];
		foreach ($this->plugin->getGroups()->getGroupsConfig()->getAll(true) as $group) {
			foreach ($this->getPlayerConfig()->getAll() as $user => $data) {
				if(strcasecmp($group, $data["group"]) === 0) {
					$return[$group][] = $user;
				}
			}
		}
		return $return;
	}
}