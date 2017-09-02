<?php
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
	public function __construct(ThePermissionManager $plugin){
		parent::__construct($plugin);
		new Config($this->plugin->getDataFolder() . "players.yml", Config::YAML, [
			"jasonwynn10" => [
				"group" => $this->plugin->getGroups()->getDefaultGroup(),
				"permissions" => [],
				"worlds" => [],
				"time" => -1
			]
		]);
	}

	/**
	 * @param IPlayer $player
	 */
	public function init(IPlayer $player) {
		$userName = strtolower($player->getName());
		if(!$this->getPlayerConfig($player)->exists($userName)) {
			$this->getPlayerConfig($player)->set($userName, [
				"group" => $this->plugin->getGroups()->getDefaultGroup(),
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
			return $config->save();
		}else{
			$config = $this->getPlayerConfig($player);
			$config->setNested(strtolower($player->getName()).".worlds.$levelName", $permissions);
			return $config->save();
		}
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return string
	 */
	public function getGroup(IPlayer $player) : string {
		return $this->getPlayerConfig($player)->getNested(strtolower($player->getName()).".group", $this->plugin->getGroups()->getDefaultGroup());
	}

	/**
	 * @param IPlayer $player
	 * @param string $group
	 *
	 * @return bool
	 */
	public function setGroup(IPlayer $player, string $group) : bool{
		$config = $this->getPlayerConfig($player);
		$config->setNested(strtolower($player->getName()).".group", $group);
		return $config->save();
	}
}