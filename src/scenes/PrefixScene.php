<?php

class PrefixScene extends AbstractScene {
    public function render()
    {
        $log = '~/game_info/logs/prefix.log';

        $this->window
            ->border()
            ->title('Create prefix')
            ->status($log)
            ->refresh();


        $progress = $this->addWidget(new ProgressBarWidget($this->window));
        $progress
            ->offset(mb_strlen($log) + 2, 3)
            ->setProgress(30)
            ->show();
    }

    public function pressKey($key) {
        app()->showMain();
        return false;
    }
}