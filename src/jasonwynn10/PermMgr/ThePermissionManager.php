<?php
namespace jasonwynn10\PermMgr;

use jasonwynn10\PermMgr\commands\ListGroupPermissions;
use jasonwynn10\PermMgr\commands\ListUserPermissions;
use jasonwynn10\PermMgr\commands\PluginPermissions;
use jasonwynn10\PermMgr\commands\ReloadPermissions;
use jasonwynn10\PermMgr\commands\SetGroup;
use jasonwynn10\PermMgr\commands\SetGroupPermission;
use jasonwynn10\PermMgr\commands\SetUserPermission;
use jasonwynn10\PermMgr\commands\UnsetGroupPermission;
use jasonwynn10\PermMgr\commands\UnsetUserPermission;
use jasonwynn10\PermMgr\event\EventListener;
use jasonwynn10\PermMgr\event\PermissionAddEvent;
use jasonwynn10\PermMgr\event\PermissionAttachEvent;
use jasonwynn10\PermMgr\event\PermissionDetachEvent;
use jasonwynn10\PermMgr\event\PermissionRemoveEvent;
use jasonwynn10\PermMgr\task\PermissionExpirationTask;

use pocketmine\lang\BaseLang;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use spoondetector\SpoonDetector;

class ThePermissionManager extends PluginBase {
	/** @var PermissionAttachment[] $perms */
	public $perms = [];

	/** @var Config $groupsConfig */
	private $groupsConfig = null;

	/** @var string $defaultGroup */
	private $defaultGroup = "";

	/** @var string[] $groupAliases */
	private $groupAliases = [];

	/** @var BaseLang $baseLang */
	private $baseLang = null;

	public function onLoad() {
		//SpoonDetector::printSpoon($this,"spoon.txt");
		$this->saveDefaultConfig();
		$this->saveResource("groups.yml");
		$this->groupsConfig = new Config($this->getDataFolder()."groups.yml", Config::YAML, [
			"Guest" => [
				"alias" => "gst",
				"isDefault" => true,
				"inheritance" => [],
				"permissions" => []
			],
			"Moderator" => [
				"alias" => "mod",
				"isDefault" => false,
				"inheritance" => [
					"Guest"
				],
				"permissions" => []
			],
			"Admin" => [
				"alias" => "adm",
				"isDefault" => false,
				"inheritance" => [
					"Moderator"
				],
				"permissions" => []
			],
			"CoOwner" => [
				"alias" => "cwn",
				"isDefault" => false,
				"inheritance" => [
					"Admin"
				],
				"permissions" => []
			],
			"Owner" => [
				"alias" => "own",
				"isDefault" => false,
				"inheritance" => [
					"CoOwner"
				],
				"permissions" => [
					"*"
				]
			]
		]);
		$this->loadGroups();
		$lang = $this->getConfig()->get("lang", BaseLang::FALLBACK_LANGUAGE);
		$this->baseLang = new BaseLang($lang,$this->getFile() . "resources/");
	}

	public function onEnable() {
		new EventListener($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new PermissionExpirationTask($this), 20);

		$this->getServer()->getCommandMap()->registerAll(self::class, [
			new SetUserPermission($this),
			new UnsetUserPermission($this),
			new ListUserPermissions($this),
			new SetGroupPermission($this),
			new UnsetGroupPermission($this),
			new ListGroupPermissions($this),
			new ReloadPermissions($this),
			new PluginPermissions($this),
			new SetGroup($this)
		]);
	}

	public function onDisable() {
		$permissions = [];
		foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
			$permissions[] = $permission->getName();
		}
		$permissions = implode(PHP_EOL, $permissions);
		@file_put_contents($this->getDataFolder()."Permission_List.txt", $permissions);
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			$this->detachPlayer($player);
		}
		$this->groupsConfig->reload();
		$groups = $this->groupsConfig->getAll();
		foreach($groups as $group => $data) {
			sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
			$this->groupsConfig->set($group, $data);
			$this->groupsConfig->save();
		}
	}

	public function getLanguage() : BaseLang {
		return $this->baseLang;
	}

	public function getGroups() : Config {
		return $this->groupsConfig;
	}

	public function getGroupAliases() : array {
		return $this->groupAliases;
	}

	private function loadGroups() {
		$groups = $this->groupsConfig->getAll();
		foreach($groups as $group => $data) {
			if(isset($data["isDefault"]) and is_bool($data["isDefault"])) {
				if($data["isDefault"] === true) {
					$this->defaultGroup = $group;
				}
			}else{
				$data["isDefault"] = false;
			}
			if(!isset($data["alias"]) or !is_string($data["alias"])) {
				$data["alias"] = '';
			}else{
				$this->groupAliases[$group] = $data["alias"];
			}
			if(!isset($data["inheritance"]) or !is_array($data["inheritance"])) {
				$data["inheritance"] = [];
			}
			if(!isset($data["permissions"]) or !is_array($data["permissions"])) {
				$data["permissions"] = [];
			}else{
				sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
			}
			if($this->getConfig()->get("enable-multiworld-perms", false) === true) {
				if(!isset($data["worlds"]) or !is_array($data["worlds"])) {
					$data["worlds"] = [];
				}
			}
			$this->groupsConfig->set($group, $data);
			$this->groupsConfig->save();
		}
	}

	# API

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function attachPlayer(Player $player) : bool {
		if(isset($this->perms[$player->getId()])) {
			return false;
		}
		$attachment = $player->addAttachment($this);
		$this->perms[$player->getId()] = $attachment;
		$this->getServer()->getPluginManager()->callEvent(new PermissionAttachEvent($this));
		@mkdir($this->getDataFolder()."players");
		@mkdir($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName());
		$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
		if(!isset($config->getAll()["group"])) {
			$config->set("group", $this->defaultGroup);
			$config->save();
		}
		$groupData = $this->groupsConfig->get($config->get("group", $this->defaultGroup), []);
		$groupPerms = $groupData["permissions"];
		sort($groupPerms, SORT_NATURAL | SORT_FLAG_CASE);
		$this->groupsConfig->setNested($config->get("group", $this->defaultGroup).".permissions", $groupPerms);
		$this->groupsConfig->save();
		$parentGroupPerms = $this->getInheritedPermissions($config->get("group", $this->defaultGroup));
		$groupPerms = array_merge($groupPerms, $parentGroupPerms);
		sort($groupPerms, SORT_NATURAL | SORT_FLAG_CASE);
		foreach($groupPerms as $permission) {
			if($this->sortPermissionConfigStrings($permission)) {
				$this->addPlayerPermission($player, new Permission($permission), true);
			}else{
				$this->removePlayerPermission($player, new Permission($permission), true);
			}
		}
		$playerPerms = $config->get("permissions", []);
		sort($playerPerms, SORT_NATURAL | SORT_FLAG_CASE);
		$config->set("permissions", $playerPerms);
		$config->save();
		foreach($playerPerms as $permission) {
			if($this->sortPermissionConfigStrings($permission)) {
				$this->removePlayerPermission($player, new Permission($permission), false);
			}else{
				$this->addPlayerPermission($player, new Permission($permission), false);
			}
		}
		return true;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function detachPlayer(Player $player) : bool {
		$this->getServer()->getPluginManager()->callEvent(new PermissionDetachEvent($this));
		$player->removeAttachment($this->perms[$player->getId()]);
		unset($this->perms[$player->getId()]);
		return true;
	}

	/**
	 * @param Player $player
	 * @param Permission $permission
	 * @param bool $group
	 *
	 * @return bool
	 */
	public function addPlayerPermission(Player $player, Permission $permission, bool $group = false) : bool {
		$ev = new PermissionAddEvent($this, $permission, $group);
		if(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true) !== true) {
			$ev->setCancelled();
			$this->removePlayerPermission($player, $permission, $ev->isGroup());
		}
		$this->getServer()->getPluginManager()->callEvent($ev);
		if($ev->isCancelled()) {
			return false;
		}

		if(!isset($this->perms[$player->getId()])) {
			return false;
		}
		$attachment = $this->perms[$player->getId()];
		$attachment->setPermission($ev->getPermission(), true);

		if(!$ev->isGroup()) {
			$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
			$data = $config->getAll()["permissions"];
			if(($key = array_search("-".$ev->getPermission()->getName(), $data)) !== false) {
				$data[$key] = $ev->getPermission()->getName();
			}elseif(($key = array_search($ev->getPermission()->getName(), $data)) !== false) {
				return false;
			}else{
				$data[] = $ev->getPermission()->getName();
			}
			sort($data, SORT_NATURAL | SORT_FLAG_CASE);
			$config->set("permissions", $data);
			$config->save();
		}
		return true;
	}

	/**
	 * @param Player $player
	 * @param Permission $permission
	 * @param bool $group
	 *
	 * @return bool
	 */
	public function removePlayerPermission(Player $player, Permission $permission, bool $group = false) : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new PermissionRemoveEvent($this, $permission, $group));
		if($ev->isCancelled() and !(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true))) {
			return false;
		}

		if(!isset($this->perms[$player->getId()])) {
			return false;
		}
		$attachment = $this->perms[$player->getId()];
		$attachment->setPermission($ev->getPermission(), false);

		if(!$ev->isGroup()) {
			$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
			$data = $config->getAll()["permissions"];
			if(($key = array_search("-".$ev->getPermission()->getName(), $data)) !== false) {
				return false;
			}elseif(($key = array_search($ev->getPermission()->getName(), $data)) !== false) {
				$data[$key] = "-".$ev->getPermission()->getName();
			}else{
				$data[] = "-".$ev->getPermission()->getName();
			}
			sort($data, SORT_NATURAL | SORT_FLAG_CASE);
			$config->set("permissions", $data);
			$config->save();
		}
		return true;
	}


	/**
	 * @param string $group
	 * @param Permission $permission
	 *
	 * @return bool
	 */
	public function addGroupPermission(string $group, Permission $permission) : bool {
		$ev = new PermissionAddEvent($this, $permission);
		if(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true) !== true) {
			$ev->setCancelled();
		}
		$this->getServer()->getPluginManager()->callEvent($ev);
		if($ev->isCancelled()) {
			return false;
		}
		if(in_array($group, $this->groupAliases)) {
			$group = array_search($group, $this->groupAliases);
		}
		$data = $this->groupsConfig->getAll()[$group];
		if(($key = array_search("-".$ev->getPermission()->getName(), $data["permissions"])) !== false) {
			$data["permissions"][$key] = $ev->getPermission()->getName();
		}elseif(($key = array_search($ev->getPermission()->getName(), $data["permissions"])) !== false) {
			return false;
		}else{
			$data["permissions"][] = $ev->getPermission()->getName();
		}
		sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
		$this->groupsConfig->set($group, $data);
		$this->groupsConfig->save();
		$this->reloadGroupPermissions();
		$this->reloadPlayerPermissions();
		return true;
	}

	/**
	 * @param string $group
	 * @param Permission $permission
	 *
	 * @return bool
	 */
	public function removeGroupPermission(string $group, Permission $permission) : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new PermissionRemoveEvent($this, $permission));
		if($ev->isCancelled() and !(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true))) {
			return false;
		}

		if(in_array($group, $this->groupAliases)) {
			$group = array_search($group, $this->groupAliases);
		}
		$data = $this->groupsConfig->getAll()[$group];
		if(($key = array_search("-".$ev->getPermission()->getName(), $data["permissions"])) !== false) {
			return false;
		}elseif(($key = array_search($ev->getPermission()->getName(), $data["permissions"])) !== false) {
			$data["permissions"][$key] = "-".$ev->getPermission()->getName();
		}else{
			$data["permissions"][] = "-".$ev->getPermission()->getName();
		}
		sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
		$this->groupsConfig->set($group, $data);
		$this->groupsConfig->save();
		$this->reloadGroupPermissions();
		$this->reloadPlayerPermissions();
		return true;
	}


	/**
	 * @param Player[] $players
	 *
	 * @return bool
	 */
	public function reloadPlayerPermissions(array $players = []) : bool {
		$players = !empty($players) ? $players : $this->getServer()->getOnlinePlayers();
		foreach($players as $player) {
			if(!isset($this->perms[$player->getId()])) {
				continue;
			}
			$player->removeAttachment($this->perms[$player->getId()]);
			unset($this->perms[$player->getId()]);
			$attachment = $player->addAttachment($this);
			$this->perms[$player->getId()] = $attachment;

			foreach($this->getPlayerPermissions($player) as $permission) {
				if($this->sortPermissionConfigStrings($permission)) {
					$this->addPlayerPermission($player, new Permission($permission), true);
				}else{
					$this->removePlayerPermission($player, new Permission($permission), true);
				}
			}
		}
		return true;
	}

	/**
	 * @param string[] $groups
	 *
	 * @return bool
	 */
	public function reloadGroupPermissions(array $groups = []) : bool {
		$this->groupsConfig->reload();
		if(!empty($groups)) {
			foreach($groups as $group) {
				$data = $this->groupsConfig->getAll()[$group];
				sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
				$this->groupsConfig->set($group, $data);
				$this->groupsConfig->save();
			}
		}else{
			$groups = $this->groupsConfig->getAll();
			foreach($groups as $group => $data) {
				sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
				$this->groupsConfig->set($group, $data);
				$this->groupsConfig->save();
			}
		}
		return true;
	}

	/**
	 * @param string $permission
	 *
	 * @return bool returns true on positive given permission and negative on taken permission
	 */
	public function sortPermissionConfigStrings(string &$permission) : bool {
		if($permission{0} === "-") {
			$permission = str_replace("-", "", $permission);
			return false;
		}else{
			return true;
		}
	}

	/**
	 * @param Player $player
	 * @param string $group
	 *
	 * @return bool
	 */
	public function setPlayerGroup(Player $player, string $group) : bool {
		$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
		if(in_array($group, $this->groupAliases)) {
			$group = array_search($group, $this->groupAliases);
		}
		$config->set("group", $group);
		$this->reloadPlayerPermissions([$player]);
		return $config->save();
	}

	/**
	 * @return Permission[]
	 */
	public function getPocketMinePermissions() : array {
		$pmPerms = [];
		/** @var Permission $permission */
		foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
			if(strpos($permission->getName(), DefaultPermissions::ROOT) !== false) {
				$pmPerms[] = $permission;
			}
		}
		return $pmPerms;
	}

	/**
	 * @param Player $player
	 *
	 * @return string[] includes set and unset permissions from config
	 */
	public function getPlayerPermissions(Player $player) : array {
		$permissions = [];
		$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
		$config->reload();
		foreach($config->getAll()["permissions"] as $permission) {
			$permissions[] = $permission;
		}
		foreach($this->getGroupPermissions($config->get("group", $this->defaultGroup)) as $permission) {
			$permissions[] = $permission;
		}
		if(($key = array_search("-*", $permissions)) !== false) {
			unset($permissions[$key]);
			foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
				$permissions[] = "-".$permission->getName();
			}
		}
		if(($key = array_search("*", $permissions)) !== false) {
			unset($permissions[$key]);
			foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
				$permissions[] = $permission->getName();
			}
		}
		return array_unique($permissions, SORT_STRING);
	}

	/**
	 * @param string $group
	 *
	 * @return string[] includes set and unset permissions from config
	 */
	public function getGroupPermissions(string $group) : array {
		$this->groupsConfig->reload();
		$permissions = [];
		if(in_array($group, $this->groupAliases)) {
			$group = array_search($group, $this->groupAliases);
		}
		foreach($this->getInheritedPermissions($group) as $permission) {
			$permissions[] = $permission;
		}
		foreach($this->groupsConfig->getAll()[$group]["permissions"] as $permission) {
			$permissions[] = $permission;
		}
		if(($key = array_search("-*", $permissions)) !== false) {
			unset($permissions[$key]);
			foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
				$permissions[] = "-".$permission->getName();
			}
		}
		if(($key = array_search("*", $permissions)) !== false) {
			unset($permissions[$key]);
			foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
				$permissions[] = $permission->getName();
			}
		}
		return array_unique($permissions, SORT_STRING);
	}

	/**
	 * @param string $group
	 *
	 * @return string[] includes set and unset permissions from config
	 */
	public function getInheritedPermissions(string $group) : array {
		$this->groupsConfig->reload();
		if(in_array($group, $this->groupAliases)) {
			$group = array_search($group, $this->groupAliases);
		}
		$permissions = [];
		foreach($this->groupsConfig->getAll()[$group]["inheritance"] as $parentGroup) {
			if(in_array($parentGroup, $this->groupAliases)) {
				$parentGroup = array_search($parentGroup, $this->groupAliases);
			}
			$parentGroupData = $this->getGroups()->getAll()[$parentGroup];
			foreach($parentGroupData["permissions"] as $parentPermission) {
				$permissions[] = $parentPermission;
			}
		}
		if(($key = array_search("-*", $permissions)) !== false) {
			unset($permissions[$key]);
			foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
				$permissions[] = "-".$permission->getName();
			}
		}
		if(($key = array_search("*", $permissions)) !== false) {
			unset($permissions[$key]);
			foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
				$permissions[] = $permission->getName();
			}
		}
		return array_unique($permissions, SORT_STRING);
	}
}