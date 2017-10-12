<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\providers;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\IPlayer;
use pocketmine\utils\Config;

class YAMLProvider extends DataProvider {
	/**
	 * YAMLProvider constructor.
	 *
	 * @param ThePermissionManager $plugin\
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin);
		@mkdir($this->plugin->getDataFolder()."players");
		@mkdir($this->plugin->getDataFolder()."players".DIRECTORY_SEPARATOR."j");
		new Config($this->plugin->getDataFolder()."players".DIRECTORY_SEPARATOR."j".DIRECTORY_SEPARATOR."permissions.yml", Config::YAML, [
			"group" => implode(", ", $this->plugin->getGroups()->getDefaultGroups()),
			"permissions" => [],
			"worlds" => []
		]);
	}

	/**
	 * @param IPlayer $player
	 */
	public function init(IPlayer $player) : void {
		@mkdir($this->plugin->getDataFolder()."players".DIRECTORY_SEPARATOR.strtolower($player->getName()));
		$config = $this->getPlayerConfig($player);
		if(!$config->exists("group")) {
			$config->set("group", implode(", ", $this->plugin->getGroups()->getDefaultGroups()));
			$config->save(true);
		}
		if(!$config->exists("permissions")) {
			$config->set("permissions", []);
			$config->save(true);
		}
		if($this->plugin->getConfig()->get("enable-multiworld-perms", false)) {
			if(!$config->exists("worlds")) {
				$config->set("worlds", []);
				$config->save(true);
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
	 * @param string $levelName
	 *
	 * @return string[]
	 */
	public function getPlayerPermissions(IPlayer $player, string $levelName = "") : array {
		if(empty($levelName)) {
			return $this->getPlayerConfig($player)->get("permissions", []);
		}else{
			return $this->getPlayerConfig($player)->getNested("worlds.$levelName", []);
		}
	}

	/**
	 * @param IPlayer $player
	 * @param array $data
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function setPlayerPermissions(IPlayer $player, array $data, string $levelName = "") : bool {
		if(empty($levelName)) {
			$config = $this->getPlayerConfig($player);
			$config->set("permissions", $data);
			return $config->save(true);
		}else{
			$config = $this->getPlayerConfig($player);
			$config->setNested("worlds.$levelName", $data);
			return $config->save(true);
		}
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return array
	 */
	public function getGroups(IPlayer $player) : array {
		return $this->getPlayerConfig($player)->get("group", $this->plugin->getGroups()->getDefaultGroups());
	}

	/**
	 * @param IPlayer $player
	 * @param array $group
	 *
	 * @return bool
	 */
	public function setGroups(IPlayer $player, array $group) : bool {
		$config = $this->getPlayerConfig($player);
		$config->set("group", $group);
		return $config->save(true);
	}

	/**
	 * @return array
	 */
	public function getGroupPlayers() : array {
		$return = [];
		foreach(new \RegexIterator(new \DirectoryIterator($this->plugin->getDataFolder()), "/\\.yml$/i") as $file){
			if($file === "." or $file === "..") {
				continue;
			}
			//TODO: figure out wth im doing
			$file = $this->plugin->getDataFolder() . $file;
			$data = yaml_parse_file($file);
			var_dump($file); //TODO: remove
			var_dump($data); //TODO: remove
			foreach ($this->plugin->getGroups()->getGroupsConfig()->getAll(true) as $group) {
				if(strcasecmp($group, $data["group"]) === 0) {
					$return[$group][] = $file;
				}
			}
		}
		return $return;
	}
}