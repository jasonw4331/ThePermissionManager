<?php
namespace jasonwynn10\PermMgr\providers;

use pocketmine\IPlayer;
use pocketmine\utils\Config;

class YAMLProvider extends DataProvider {
	/**
	 * @param IPlayer $player
	 */
	public function init(IPlayer $player) {
		@mkdir($this->plugin->getDataFolder()."players");
		@mkdir($this->plugin->getDataFolder()."players".DIRECTORY_SEPARATOR.strtolower($player));
		$config = $this->getPlayerConfig($player);
		if(!$config->exists("group")) {
			$config->set("group", $this->plugin->getGroups()->getDefaultGroup());
			$config->save();
		}
		if($this->plugin->getConfig()->get("enable-multiworld-perms", false)) {
			if(!$config->exists("worlds")) {
				$config->set("worlds", []);
			}
		}
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return Config
	 */
	public function getPlayerConfig(IPlayer $player) : Config {
		return new Config($this->plugin->getDataFolder()."players".DIRECTORY_SEPARATOR.strtolower($player->getName()).DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return string[]
	 */
	public function getPlayerPermissions(IPlayer $player) : array {
		return $this->getPlayerConfig($player)->get("permissions", []);
	}

	/**
	 * @param IPlayer $player
	 * @param array $data
	 *
	 * @return bool
	 */
	public function setPlayerPermissions(IPlayer $player, array $data) : bool {
		$config = $this->getPlayerConfig($player);
		$config->set("permissions", $data);
		return $config->save();
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return string
	 */
	public function getGroup(IPlayer $player) : string {
		return $this->getPlayerConfig($player)->get("group", $this->plugin->getGroups()->getDefaultGroup());
	}
}