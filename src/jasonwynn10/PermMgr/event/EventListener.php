<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDataSaveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;

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

	/**
	 * @priority MONITOR
	 * @ignoreCancelled true
	 *
	 * @param PlayerDataSaveEvent $ev
	 */
	public function onPlayerSave(PlayerDataSaveEvent $ev) {
		$player = $ev->getPlayer();
		if($player->getPlayer() instanceof Player) {
			if($this->plugin->isAttached($player->getPlayer())) {
				$this->plugin->getPlayerProvider()->sortPlayerPermissions($player);
			}
		}
	}
}