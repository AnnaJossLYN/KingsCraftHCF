<?php

namespace hcf\command\types;

use hcf\command\utils\Command;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use PDO;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class AddLivesCommand extends Command {

    /**
     * AddLivesCommand constructor.
     */
    public function __construct() {
        parent::__construct("addlives", "Add lives to a player.", "/addlives <player> <amount>");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender->isOp()) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $player = $this->getCore()->getServer()->getPlayer($args[0]);
        if(!$player instanceof HCFPlayer) {
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT lives FROM players WHERE username = :username");
            $stmt->bindParam(":username", $args[0]);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $lives = $row['lives'];
                if($lives === null) {
                    $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                    return;
                }
            }
            $stmt->closeCursor();

        }
        if(!is_numeric($args[1])) {
            $sender->sendMessage(Translation::getMessage("notNumeric"));
            return;
        }
        if(isset($lives)) {
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET lives = lives + :lives WHERE username = :username");
            $stmt->bindParam(":lives", $args[1]);
            $stmt->bindParam(":username", $args[0]);
            $stmt->execute();
            $stmt->closeCursor();
        }
        else {
            $player->addLives($args[1]);
            $player->sendMessage(Translation::getMessage("lives", [
                "amount" => TextFormat::GREEN . $player->getLives()
            ]));
        }
        $sender->sendMessage(Translation::getMessage("addLivesSuccess", [
            "amount" => TextFormat::GREEN . $args[1],
            "name" => TextFormat::GOLD . $player instanceof HCFPlayer ? $player->getName() : $args[0]
        ]));
    }
}