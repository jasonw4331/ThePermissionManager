<?php
namespace jasonwynn10\PermMgr\providers;

use pocketmine\IPlayer;
use pocketmine\utils\Config;

class PurePermsProvider extends DataProvider {
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
	public function getPlayerConfig(IPlayer $player) : Config {
		return new Config($this->plugin->getDataFolder() . "players.yml", Config::YAML);
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return array
	 */
	public function getPlayerPermissions(IPlayer $player) : array {
		return $this->getPlayerConfig($player)->getNested(strtolower($player->getName()).".permissions", []);
	}

	/**
	 * @param IPlayer $player
	 * @param array $permissions
	 *
	 * @return bool
	 */
	public function setPlayerPermissions(IPlayer $player, array $permissions) : bool {
		$config = $this->getPlayerConfig($player);
		$config->setNested(strtolower($player->getName()).".permissions", $permissions);
		return $config->save();
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return string
	 */
	public function getGroup(IPlayer $player) : string {
		return $this->getPlayerConfig($player)->getNested(strtolower($player->getName()).".group", $this->plugin->getGroups()->getDefaultGroup());
	}
}