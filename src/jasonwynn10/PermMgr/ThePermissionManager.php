<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr;

use jasonwynn10\PermMgr\commands\DefaultGroup;
use jasonwynn10\PermMgr\commands\GroupInformation;
use jasonwynn10\PermMgr\commands\Groups;
use jasonwynn10\PermMgr\commands\ListGroupPermissions;
use jasonwynn10\PermMgr\commands\ListUserPermissions;
use jasonwynn10\PermMgr\commands\MergeUsers;
use jasonwynn10\PermMgr\commands\PluginPermissions;
use jasonwynn10\PermMgr\commands\ReloadPermissions;
use jasonwynn10\PermMgr\commands\SetGroup;
use jasonwynn10\PermMgr\commands\SetGroupPermission;
use jasonwynn10\PermMgr\commands\SetUserPermission;
use jasonwynn10\PermMgr\commands\UnsetGroupPermission;
use jasonwynn10\PermMgr\commands\UnsetUserPermission;
use jasonwynn10\PermMgr\commands\UserInformation;
use jasonwynn10\PermMgr\event\EventListener;
use jasonwynn10\PermMgr\event\GroupChangeEvent;
use jasonwynn10\PermMgr\event\GroupPermissionAddEvent;
use jasonwynn10\PermMgr\event\GroupPermissionRemoveEvent;
use jasonwynn10\PermMgr\event\PermissionAttachEvent;
use jasonwynn10\PermMgr\event\PermissionDetachEvent;
use jasonwynn10\PermMgr\event\PlayerPermissionAddEvent;
use jasonwynn10\PermMgr\event\PlayerPermissionRemoveEvent;
use jasonwynn10\PermMgr\providers\DataProvider;
use jasonwynn10\PermMgr\providers\GroupManager;
use jasonwynn10\PermMgr\providers\MySQLProvider;
use jasonwynn10\PermMgr\providers\PurePermsProvider;
use jasonwynn10\PermMgr\providers\SQLite3Provider;
use jasonwynn10\PermMgr\providers\YAMLProvider;

use pocketmine\IPlayer;
use pocketmine\lang\BaseLang;
use pocketmine\utils\Config;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

use spoondetector\SpoonDetector;

class ThePermissionManager extends PluginBase {
	/** @var PermissionAttachment[] $perms */
	public $perms = [];

	/** @var DataProvider $playerProvider */
	private $playerProvider;

	/** @var GroupManager $groupProvider */
	private $groupProvider;

	/** @var string[] $superAdmins */
	private $superAdmins = [];

	/** @var BaseLang $baseLang */
	private $baseLang = null;

	public function onLoad() : void {
		$this->saveDefaultConfig();
		$this->groupProvider = new GroupManager($this);
		if(file_exists($this->getServer()->getPluginPath()."PurePerms") and strtolower($this->getConfig()->get("data-provider", "pureperms")) === "pureperms") {
			$this->importPurePerms(); // only works with the yamlv2 provider in PurePerms
		}
		$this->getConfig()->reload();
		/** @var string $lang */
		$lang = $this->getConfig()->get("lang", BaseLang::FALLBACK_LANGUAGE);
		$this->baseLang = new BaseLang($lang,$this->getFile() . "resources/");
		if(!isset($this->playerProvider)) {
			switch(strtolower($this->getConfig()->get("data-provider", "pureperms"))) {
				case "mysql":
					$this->playerProvider = new MySQLProvider($this);
				break;
				case "sqlite3":
				case "sqlite":
					$this->playerProvider = new SQLite3Provider($this);
				break;
				case "yaml":
					$this->playerProvider = new YAMLProvider($this);
				break;
				case "pureperms":
				default:
					$this->playerProvider = new PurePermsProvider($this);
				break;
			}
		}
		$this->superAdmins = $this->getConfig()->get("superadmin-groups", []);
		$this->getServer()->getCommandMap()->registerAll(self::class, [
			new SetUserPermission($this),
			new UnsetUserPermission($this),
			new ListUserPermissions($this),
			new MergeUsers($this),
			new SetGroupPermission($this),
			new UnsetGroupPermission($this),
			new ListGroupPermissions($this),
			new ReloadPermissions($this),
			new PluginPermissions($this),
			new SetGroup($this),
			new Groups($this),
			new UserInformation($this),
			new GroupInformation($this),
			new DefaultGroup($this)
		]);
	}

	public function onEnable() : void {
		new EventListener($this);

		SpoonDetector::printSpoon($this,"spoon.txt");

		//TODO find and disable other permission managers
		/** @var \_64FF00\PurePerms\PurePerms|null $pureperms */
		$pureperms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
		if($pureperms !== null) {
			$pureperms->setEnabled(false);
		}
		/** @var \SOFe\SimplePermissions\SimplePermissions|null $simplePerms */
		$simplePerms = $this->getServer()->getPluginManager()->getPlugin("SimplePermissions");
		if($simplePerms !== null) {
			$simplePerms->setEnabled(false);
		}
	}

	public function onDisable() : void {
		@unlink($this->getDataFolder()."Permission_List.txt");
		$permissions = [];
		foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
			$permissions[] = $permission->getName();
		}
		@file_put_contents($this->getDataFolder()."Permission_List.txt", implode(PHP_EOL, $permissions));
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			$this->detachPlayer($player);
		}
	}

	public function importPurePerms() : void {
		//Players
		if(file_exists($this->getServer()->getPluginPath()."PurePerms".DIRECTORY_SEPARATOR."players.yml")) {
			$importedData = (new Config($this->getServer()->getPluginPath()."PurePerms".DIRECTORY_SEPARATOR."players.yml", Config::YAML))->getAll();
			$this->playerProvider = new PurePermsProvider($this);
			$this->playerProvider->getPlayerConfig()->setAll($importedData);
			$this->playerProvider->getPlayerConfig()->save(true);
		}
		//Groups
		if(file_exists($this->getServer()->getPluginPath()."PurePerms".DIRECTORY_SEPARATOR."groups.yml")) {
			$importedData = (new Config($this->getServer()->getPluginPath()."PurePerms".DIRECTORY_SEPARATOR."groups.yml", Config::YAML))->getAll();
			$this->groupProvider->getGroupsConfig()->setAll($importedData);
			$this->groupProvider->getGroupsConfig()->save(true);
		}
	}

	public function getLanguage() : BaseLang {
		return $this->baseLang;
	}

	public function getGroups() : GroupManager {
		return $this->groupProvider;
	}

	public function getPlayerProvider() : DataProvider {
		return $this->playerProvider;
	}

	# API

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function attachPlayer(Player $player) : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new PermissionAttachEvent($this, $player));
		if($this->isAttached($ev->getplayer())) {
			return false;
		}
		$attachment = $ev->getplayer()->addAttachment($this);
		$this->perms[$ev->getplayer()->getId()] = $attachment;
		$this->playerProvider->init($ev->getplayer());
		if(!$this->getConfig()->get("enable-multiworld-perms", false)) {
			foreach($this->playerProvider->getGroups($ev->getplayer()) as $grp) {
				$groupPerms = $this->getGroups()->getAllGroupPermissions($grp);
				foreach($groupPerms as $permission) {
					if($this->sortPermissionConfigStrings($permission)) {
						$this->addPlayerPermission($ev->getplayer(), new Permission($permission), true);
					}else{
						$this->removePlayerPermission($ev->getplayer(), new Permission($permission), true);
					}
				}
				$this->playerProvider->sortPlayerPermissions($ev->getplayer());
				$playerPerms = $this->playerProvider->getPlayerPermissions($ev->getplayer());
				foreach($playerPerms as $permission) {
					if($this->sortPermissionConfigStrings($permission)) {
						$this->removePlayerPermission($ev->getplayer(), new Permission($permission), false);
					}else{
						$this->addPlayerPermission($ev->getplayer(), new Permission($permission), false);
					}
				}
			}
		}else{
			foreach ($this->playerProvider->getGroups($ev->getplayer()) as $grp) {
				$groupPerms = $this->getGroups()->getAllGroupPermissions($grp, $player->getLevel()->getName());
				foreach($groupPerms as $permission) {
					if($this->sortPermissionConfigStrings($permission)) {
						$this->addPlayerPermission($ev->getplayer(), new Permission($permission), true, $player->getLevel()->getName());
					}else{
						$this->removePlayerPermission($ev->getplayer(), new Permission($permission), true, $player->getLevel()->getName());
					}
				}
				$this->playerProvider->sortPlayerPermissions($ev->getplayer());
				$playerPerms = $this->playerProvider->getPlayerPermissions($ev->getplayer(), $player->getLevel()->getName());
				foreach($playerPerms as $permission) {
					if($this->sortPermissionConfigStrings($permission)) {
						$this->removePlayerPermission($ev->getplayer(), new Permission($permission), false, $player->getLevel()->getName());
					}else{
						$this->addPlayerPermission($ev->getplayer(), new Permission($permission), false, $player->getLevel()->getName());
					}
				}
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
		$this->getServer()->getPluginManager()->callEvent($ev = new PermissionDetachEvent($this, $player));
		$ev->getPlayer()->removeAttachment($this->perms[$ev->getPlayer()->getId()]);
		unset($this->perms[$ev->getPlayer()->getId()]);
		return true;
	}

	/**
	 * @param Player|IPlayer $player
	 *
	 * @return bool
	 */
	public function isAttached(IPlayer $player) : bool {
		if(!$player->isOnline())
			return false;
		return isset($this->perms[$player->getId()]);
	}

	/**
	 * @param IPlayer $player
	 * @param Permission $permission
	 * @param bool $group
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function addPlayerPermission(IPlayer $player, Permission $permission, bool $group = false, string $levelName = "") : bool {
		$ev = new PlayerPermissionAddEvent($this, $player, $permission, $group, $levelName);
		if(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true) !== true) {
			$ev->setCancelled();
		}
		$this->getServer()->getPluginManager()->callEvent($ev);
		if(!$ev->isGroup()) {
			$this->playerProvider->setPlayerPermissions($ev->getPlayer(), [$permission->getName()], $ev->getLevelName());
			$this->playerProvider->sortPlayerPermissions($ev->getPlayer());
		}
		if($ev->getPlayer()->isOnline()) {
			if($ev->isCancelled() or !$this->isAttached($ev->getPlayer())) {
				return false;
			}
			/** @noinspection PhpUndefinedMethodInspection */
			$attachment = $this->perms[$ev->getPlayer()->getId()];
			$attachment->setPermission($ev->getPermission(), true);
		}
		return true;
	}

	/**
	 * @param IPlayer $player
	 * @param Permission $permission
	 * @param bool $group
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function removePlayerPermission(IPlayer $player, Permission $permission, bool $group = false, string $levelName = "") : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new PlayerPermissionRemoveEvent($this, $player, $permission, $group, $levelName));
		if(!$ev->isGroup()) {
			$this->playerProvider->removePlayerPermissions($ev->getPlayer(), [$permission->getName()], $ev->getLevelName());
			$this->playerProvider->sortPlayerPermissions($ev->getPlayer());
		}
		if($ev->getPlayer()->isOnline()) {
			if(!$this->isAttached($ev->getPlayer()) or ($ev->isCancelled() and !(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true)))) {
				return false;
			}
			/** @noinspection PhpUndefinedMethodInspection */
			$attachment = $this->perms[$ev->getPlayer()->getId()];
			$attachment->setPermission($ev->getPermission(), false);
		}
		return true;
	}

	/**
	 * @param string $group
	 * @param Permission $permission
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function addGroupPermission(string $group, Permission $permission, string $levelName = "") : bool {
		$ev = new GroupPermissionAddEvent($this, $group, $permission, $levelName);
		if(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true) !== true) {
			$ev->setCancelled();
		}
		$this->getServer()->getPluginManager()->callEvent($ev);
		if($ev->isCancelled()) {
			return false;
		}
		$this->groupProvider->addGroupPermissions($ev->getGroup(), [$permission->getName()], $ev->getLevelName());
		$this->groupProvider->sortGroupPermissions($ev->getGroup());
		$this->reloadGroupPermissions();
		$this->reloadPlayerPermissions();
		return true;
	}

	/**
	 * @param string $group
	 * @param Permission $permission
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function removeGroupPermission(string $group, Permission $permission, string $levelName = "") : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new GroupPermissionRemoveEvent($this, $group, $permission, $levelName));
		if($ev->isCancelled() and !(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true))) {
			return false;
		}

		$this->groupProvider->removeGroupPermissions($ev->getGroup(), [$permission->getName()], $ev->getLevelName());
		$this->groupProvider->sortGroupPermissions($ev->getGroup());
		$this->reloadGroupPermissions();
		$this->reloadPlayerPermissions();
		return true;
	}

	/**
	 * @param IPlayer[] $players
	 *
	 * @return bool
	 */
	public function reloadPlayerPermissions(array $players = []) : bool {
		$players = !empty($players) ? $players : $this->getServer()->getOnlinePlayers();
		foreach($players as $player) {
			if(!$player->isOnline() or !$this->isAttached($player)) {
				continue;
			}
			$player->removeAttachment($this->perms[$player->getId()]);
			unset($this->perms[$player->getId()]);
			$attachment = $player->addAttachment($this);
			$this->perms[$player->getId()] = $attachment;

			foreach($this->playerProvider->getPlayerPermissions($player) as $permission) {
				if($this->sortPermissionConfigStrings($permission)) {
					$this->addPlayerPermission($player, new Permission($permission), false);
				}else{
					$this->removePlayerPermission($player, new Permission($permission), false);
				}
			}
			foreach($this->playerProvider->getAllPlayerPermissions($player) as $permission) {
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
		return $this->groupProvider->reloadGroupPermissions($groups);
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
	 * @param string &$group returns the full group name
	 *
	 * @return bool
	 */
	public function isAlias(string &$group) : bool {
		return $this->getGroups()->isAlias($group);
	}

	/**
	 * @param IPlayer $player
	 * @param array $groups
	 *
	 * @return bool
	 */
	public function setPlayerGroups(IPlayer $player, array $groups) : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new GroupChangeEvent($this, $player, $this->playerProvider->getGroups($player), $groups));
		if($ev->isCancelled())
			return false;
		$return = $this->playerProvider->setGroups($ev->getPlayer(), $ev->getNewGroups());
		$this->reloadPlayerPermissions([$ev->getPlayer()]);
		return $return;
	}

	/**
	 * @param IPlayer $player
	 * @param string $group
	 *
	 * @return bool
	 */
	public function addPlayerGroup(IPlayer $player, string $group) : bool {
		$groups = $this->playerProvider->getGroups($player);
		$groups[] = $group;
		$this->getServer()->getPluginManager()->callEvent($ev = new GroupChangeEvent($this, $player, $this->playerProvider->getGroups($player), array_unique($groups)));
		if($ev->isCancelled())
			return false;
		$return = $this->playerProvider->setGroups($ev->getPlayer(), $ev->getNewGroups());
		$this->reloadPlayerPermissions([$ev->getPlayer()]);
		return $return;
	}

	/**
	 * @param IPlayer $player
	 * @param string $group
	 *
	 * @return bool
	 */
	public function removePlayerGroup(IPlayer $player, string $group) : bool {
		$groups = $this->playerProvider->getGroups($player);
		$key = array_search($group, $groups);
		if($key !== null) {
			unset($groups[$key]);
			$this->getServer()->getPluginManager()->callEvent($ev = new GroupChangeEvent($this, $player, $this->playerProvider->getGroups($player), array_unique($groups)));
			if($ev->isCancelled())
				return false;
			$return = $this->playerProvider->setGroups($ev->getPlayer(), $ev->getNewGroups());
			$this->reloadPlayerPermissions([$ev->getPlayer()]);
			return $return;
		}
		return false;
	}

	/**
	 * @return string[]
	 */
	public function getSuperAdmins() : array {
		return $this->superAdmins;
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
}