Sem se kopírují upravené soubory z Nette frameworku.

Bohužel vše nejde jen tak zkopírovat, protože jiné soubory requirují pùvodní soubor 
i s originální cestou, takže pøidávám návod, co kde upravit pøi update Nette.

PresenterComponent.php
-----------
- odebrat z ArrayAccess metod klíèové slovo final

Presenter.php
-------------
    zmena na protected:
    protected $ajaxMode;
    
    

    protected function sendPayload()
    {                             
+        $this->payload->_a = $this->getParam('-a');
+        $this->payload->_r = $this->getParam('-r');