<?php

try {
    $installer = $this;

    $installer->installEntities();

    Mage::getSingleton('adminhtml/session')->addSuccess("Meanbee: Visual Stock Levels Installer Successful: 1.0.0.");
} catch (Exception $e) {
    Mage::getSingleton('adminhtml/session')->addError("An error has occurred installing Meanbee: Visual Stock Levels: ". $e->getMessage());
} 

