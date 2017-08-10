<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class SetGroupPermission extends PluginCommand {
	/**
	 * SetGroupPermission constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("setgrouppermission.name"), $plugin);
		$this->setPermission("PermManager.command.setgrouppermission");
		$this->setUsage($plugin->getLanguage()->get("setgrouppermission.usage"));
		$this->setAliases([$plugin->getLanguage()->get("setgrouppermission.alias")]);
		$this->setDescription($plugin->getLanguage()->get("setgrouppermission.desc"));
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)) {
			return true;
		}
		if(empty($args)) {
			return false;
		}
		$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
		if($player instanceof Player) {
			if(isset($args[1])) {
				if($args[1]{0} == "-") {
					$permString = str_replace("-","", $args[1]);
					if($permString == "*") {
						foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
							$this->getPlugin()->removePlayerPermission($player, $permission);
						}
					}
				}else{
					if($args[1] == "*") {
						foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
							$this->getPlugin()->addPlayerPermission($player, $permission);
						}
					}
				}
				$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setgrouppermission.success", [$args[0]]));
			}else{
				return false;
			}
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

	/**
	 * @param Player $player
	 *
	 * @return array
	 */
	public function generateCustomCommandData(Player $player) : array {
		$commandData = parent::generateCustomCommandData($player);
		$groups = [];
		foreach($this->getPlugin()->getGroups() as $group => $data) {
			$groups[] = $group;
		}
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "group",
				"type" => "stringenum",
				"optional" => false,
				"enumtext" => $groups
			],
			[
				"name" => "permission",
				"type" => "rawtext",
				"optional" => false
			]
		];
		$commandData["permission"] = $this->getPermission();
		return $commandData;
	}
}