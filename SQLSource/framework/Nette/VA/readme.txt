Sem se kop�ruj� upraven� soubory z Nette frameworku.

Bohu�el v�e nejde jen tak zkop�rovat, proto�e jin� soubory requiruj� p�vodn� soubor 
i s origin�ln� cestou, tak�e p�id�v�m n�vod, co kde upravit p�i update Nette.

PresenterComponent.php
-----------
- odebrat z ArrayAccess metod kl��ov� slovo final

Presenter.php
-------------
    zmena na protected:
    protected $ajaxMode;
    
    

    protected function sendPayload()
    {                             
+        $this->payload->_a = $this->getParam('-a');
+        $this->payload->_r = $this->getParam('-r');