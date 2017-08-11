<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDataSaveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\Config;

class EventListener implements Listener {
	/** @var ThePermissionManager $plugin */
	private $plugin;

	public function __construct(ThePermissionManager $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * Lowest priority to give players the permissions to bypass login systems
	 * @priority LOWEST
	 * @ignoreCancelled false
	 *
	 * @param PlayerPreLoginEvent $ev
	 */
	public function preLoginEvent(PlayerPreLoginEvent $ev) {
		if($ev->isCancelled()) {
			return;
		}
		$this->plugin->attachPlayer($ev->getPlayer());
	}

	/**
	 * @priority MONITOR
	 * @ignoreCancelled true
	 *
	 * @param PlayerQuitEvent $ev
	 */
	public function onQuit(PlayerQuitEvent $ev) {
		$this->plugin->detachPlayer($ev->getPlayer());
	}

	/**
	 * @priority MONITOR
	 * @ignoreCancelled true
	 *
	 * @param PlayerDataSaveEvent $ev
	 */
	public function onPlayerSave(PlayerDataSaveEvent $ev) {
		$player = $ev->getPlayer();
		$config = new Config($this->plugin->getDataFolder()."players".DIRECTORY_SEPARATOR.strtolower($player->getName()).DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
		$data = $config->getAll()["permissions"];
		sort($data, SORT_NATURAL | SORT_FLAG_CASE);
		$config->set("permissions", $data);
		if($this->plugin->getConfig()->get("enable-multiworld-perms", false)) {
			if(!$config->exists("worlds")) {
				$config->set("worlds", []);
			}
		}
		$config->save();
	}
}