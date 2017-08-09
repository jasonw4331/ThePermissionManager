<?php
namespace jasonwynn10\PermMgr;

use jasonwynn10\PermMgr\commands\ListUserPermissions;
use jasonwynn10\PermMgr\commands\PluginPermissions;
use jasonwynn10\PermMgr\commands\ReloadPermissions;
use jasonwynn10\PermMgr\commands\SetUserPermission;
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

	/** @var BaseLang $baseLang */
	private $baseLang = null;

	public function onLoad() {
		SpoonDetector::printSpoon($this,"spoon.txt");
		$this->saveDefaultConfig();
		$resource = $this->getResource("groups.yml");
		$this->groupsConfig = new Config($this->getDataFolder()."groups.yml", Config::YAML, yaml_parse(stream_get_contents($resource), -1));
		fclose($resource);
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
			new ReloadPermissions($this),
			new PluginPermissions($this),
			new ListUserPermissions($this)
		]);
	}

	public function onDisable() {
		foreach($this->getServer()->getOnlinePlayers() as $player){
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

	private function loadGroups() {
		$groups = $this->groupsConfig->getAll();
		foreach($groups as $group => $data) {
			if(isset($data["isDefault"])) {
				if($data["isDefault"] === true) {
					$this->defaultGroup = $group;
				}
			}else{
				$data["isDefault"] = false;
			}
			if(!isset($data["alias"])) {
				$data["alias"] = '';
			}
			if(!isset($data["inheritance"])) {
				$data["inheritance"] = [];
			}
			if(!isset($data["permissions"])) {
				$data["permissions"] = [];
			}else{
				sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
			}
			if($this->getConfig()->get("enable-multiworld-perms", false) === true) {
				if(!isset($data["worlds"])) {
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
		$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
		if(!isset($config->getAll()["group"])) {
			$config->set("group", $this->defaultGroup);
			$config->save();
		}
		$groupData = $this->groupsConfig->get($config->get("group", $this->defaultGroup));
		foreach($groupData as $data) {
			$groupPerms = $data["permissions"];
			sort($groupPerms, SORT_NATURAL | SORT_FLAG_CASE);
			$this->groupsConfig->setNested($config->get("group", $this->defaultGroup).".permissions", $groupPerms);
			$this->groupsConfig->save();
			foreach($data["permissions"] as $permission) {
				if($permission{0} === "-") {
					$permission = str_replace("-", "", $permission);
					$this->removePlayerPermission($player, new Permission($permission));
					//$attachment->setPermission($permission, false);
				}else{
					$this->addPlayerPermission($player, new Permission($permission));
				}
			}
		}
		$playerPerms = $config->get("permissions", []);
		sort($playerPerms, SORT_NATURAL | SORT_FLAG_CASE);
		$config->set("permissions", $playerPerms);
		$config->save();
		foreach($playerPerms as $permission) {
			if($permission{0} === "-") {
				$permission = str_replace("-", "", $permission);
				$this->removePlayerPermission($player, new Permission($permission));
				//$attachment->setPermission($permission, false);
			}else{
				$this->addPlayerPermission($player, new Permission($permission));
				//$attachment->setPermission($permission, true);
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
	 *
	 * @return bool
	 */
	public function addPlayerPermission(Player $player, Permission $permission) : bool {
		$ev = new PermissionAddEvent($this, $permission);
		if(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true) !== true) {
			$ev->setCancelled();
			$this->removePlayerPermission($player, $permission);
		}
		$this->getServer()->getPluginManager()->callEvent($ev);
		if($ev->isCancelled()) {
			return false;
		}

		if(isset($this->perms[$player->getId()])) {
			$attachment = $this->perms[$player->getId()];
		}else{
			$this->perms[$player->getId()] = $player->addAttachment($this, $ev->getPermission()->getName(), true);
			return true;
		}
		$attachment->setPermission($ev->getPermission(), true);
		return true;
	}

	/**
	 * @param Player $player
	 * @param Permission $permission
	 *
	 * @return bool
	 */
	public function RemovePlayerPermission(Player $player, Permission $permission) : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new PermissionRemoveEvent($this, $permission));
		if($ev->isCancelled() and !(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true))) {
			return false;
		}

		$attachment = $this->perms[$player->getId()];
		$attachment->setPermission($ev->getPermission(), false);
		return true;
	}

	/**
	 * @return bool
	 */
	public function reloadPlayerPermissions() : bool {
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			if(!isset($this->perms[$player->getId()])) {
				continue;
			}
			$attachment = $player->addAttachment($this);
			$this->perms[$player->getId()] = $attachment;
			$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName(){0}.DIRECTORY_SEPARATOR."{$player->getLowerCaseName()}.yml", Config::YAML);
			//$config->reload();
			if(!isset($config->getAll()["group"])) {
				$config->set("group", $this->defaultGroup);
				$config->save();
			}
			$groupData = $this->groupsConfig->get($config->get("group", $this->defaultGroup));
			foreach($groupData as $data) {
				$groupPerms = $data["permissions"];
				sort($groupPerms, SORT_NATURAL | SORT_FLAG_CASE);
				$this->groupsConfig->setNested($config->get("group", $this->defaultGroup).".permissions", $groupPerms);
				$this->groupsConfig->save();
				foreach($data["permissions"] as $permission) {
					if($permission{0} === "-") {
						$permission = str_replace("-", "", $permission);
						$this->removePlayerPermission($player, new Permission($permission));
						//$attachment->setPermission($permission, false);
					}else{
						$this->addPlayerPermission($player, new Permission($permission));
						//$attachment->setPermission($permission, true);
					}
				}
			}
			$playerPerms = $config->get("permissions", []);
			sort($playerPerms, SORT_NATURAL | SORT_FLAG_CASE);
			$config->set("permissions", $playerPerms);
			$config->save();
			foreach($playerPerms as $permission) {
				if($permission{0} === "-") {
					$permission = str_replace("-", "", $permission);
					$this->removePlayerPermission($player, new Permission($permission));
					//$attachment->setPermission($permission, false);
				}else{
					$this->addPlayerPermission($player, new Permission($permission));
					//$attachment->setPermission($permission, true);
				}
			}
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function reloadGroupPermissions() : bool {
		$this->getGroups()->reload();
		$groups = $this->groupsConfig->getAll();
		foreach($groups as $group => $data) {
			sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
			$this->groupsConfig->set($group, $data);
			$this->groupsConfig->save();
		}
		return true;
	}

	/**
	 * @return Permission[]
	 */
	public function getPocketMinePerms() : array {
		$pmPerms = [];
		/** @var Permission $permission */
		foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
			if(strpos($permission->getName(), DefaultPermissions::ROOT) !== false) {
				$pmPerms[] = $permission;
			}
		}
		return $pmPerms;
	}
}