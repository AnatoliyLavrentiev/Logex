<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250321180609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le champ order_number aux commandes existantes avec des valeurs uniques CMD-XXXXXX';
    }

    public function up(Schema $schema): void
    {
        // Étape 1: Ajouter la colonne en tant que nullable
        $this->addSql('ALTER TABLE orders ADD order_number VARCHAR(20) DEFAULT NULL');

        // Étape 2: Générer des valeurs pour les commandes existantes
        $this->addSql("UPDATE orders SET order_number = CONCAT('CMD-', LPAD(id::text, 6, '0'))");

        // Étape 3: Rendre la colonne NOT NULL + unique
        $this->addSql('ALTER TABLE orders ALTER COLUMN order_number SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ORDER_ORDER_NUMBER ON orders (order_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_ORDER_ORDER_NUMBER');
        $this->addSql('ALTER TABLE orders DROP order_number');
    }
}
