<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener {
	/** @var ThePermissionManager $plugin */
	private $plugin;

	/**
	 * EventListener constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * Lowest priority to give players the permissions to attempt to bypass some login systems
	 * @priority LOWEST
	 * @ignoreCancelled true
	 *
	 * @param PlayerJoinEvent $ev
	 */
	public function onJoin(PlayerJoinEvent $ev) {
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
}