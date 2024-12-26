<?php

class CommandExecutor
{
    public function executeCommand(Command $command)
    {
        $command->execute();
    }
}
