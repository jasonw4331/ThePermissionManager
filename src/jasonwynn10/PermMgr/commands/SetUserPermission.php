<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class SetUserPermission extends PluginCommand {
	/**
	 * SetUserPermission constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("setuserpermission.name"), $plugin);
		$this->setPermission("PermManager.command.setuserpermission");
		$this->setUsage($plugin->getLanguage()->get("setuserpermission.usage"));
		$this->setAliases([$plugin->getLanguage()->get("setuserpermission.usage")]);
		$this->setDescription($plugin->getLanguage()->get("setuserpermission.desc"));
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args){
		parent::execute($sender, $commandLabel, $args);
		$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
		if($player instanceof Player) {
			$perm = new Permission($args[1]);
			$this->getPlugin()->addPlayerPermission($player, $perm);
			$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$args[0]]));
		} else {
			$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("playeroffline", [$args[0]]));
		}
		return true;
	}

	/**
	 * @return ThePermissionManager
	 */
	public function getPlugin() : Plugin {
		return parent::getPlugin();
	}
}