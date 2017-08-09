<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener {
	/** @var ThePermissionManager $plugin */
	private $plugin;

	public function __construct(ThePermissionManager $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * @priority LOWEST
	 * @ignoreCancelled false
	 *
	 * @param PlayerJoinEvent $ev
	 */
	public function onJoin(PlayerJoinEvent $ev) {
		$this->plugin->attachPlayer($ev->getPlayer());
	}

	/**
	 * @priority MONITOR
	 *
	 * @param PlayerQuitEvent $ev
	 */
	public function onQuit(PlayerQuitEvent $ev) {
		$this->plugin->detachPlayer($ev->getPlayer());
	}
}