<?php
namespace jasonwynn10\PermMgr\providers;

use pocketmine\IPlayer;
use pocketmine\utils\Config;

class YAMLProvider extends DataProvider {

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
	 * @param array $data
	 *
	 * @return bool
	 */
	public function setPlayerPermissions(IPlayer $player, array $data) : bool {
		$config = $this->getPlayerConfig($player);
		$config->set("permissions", $data);
		$config->save();
		return $this->sortPlayerPermissions($player);
	}

	/**
	 * @param IPlayer $player
	 * @param array $data
	 *
	 * @return bool
	 */
	public function addPlayerPermissions(IPlayer $player, array $data = []) : bool {
		$permissions = $this->getPlayerPermissions($player);
		array_merge($permissions, $data);
		return $this->setPlayerPermissions($player, $data);
	}

	/**
	 * @param IPlayer $player
	 * @param string[] $permissions
	 *
	 * @return bool
	 */
	public function removePlayerPermissions(IPlayer $player, array $permissions = []) : bool {
		$perms = $this->getPlayerPermissions($player);
		foreach($permissions as $permission) {
			if(($key = array_search($permission, $this->getPlayerPermissions($player))) !== false) {
				unset($perms[$key]);
			}
		}
		return $this->setPlayerPermissions($player, $perms);
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
	 *
	 * @return string[]
	 */
	public function getAllPlayerPermissions(IPlayer $player) : array{
		$playerPerms = array_merge($this->getPlayerPermissions($player), $this->plugin->getGroups()->getAllGroupPermissions($this->getGroup($player)));
		return array_unique($playerPerms);
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
	 * @return string
	 */
	public function getGroup(IPlayer $player) : string {
		return $this->getPlayerConfig($player)->get("group", $this->plugin->getGroups()->getDefaultGroup());
	}
}