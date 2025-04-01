<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250326084343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT fk_52ea1f094584665a');
        $this->addSql('DROP INDEX idx_52ea1f094584665a');
        $this->addSql('ALTER TABLE order_item ADD unit_price DOUBLE PRECISION DEFAULT 0');
        $this->addSql('ALTER TABLE order_item RENAME COLUMN product_id TO inventory_id');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F099EEA759 FOREIGN KEY (inventory_id) REFERENCES inventory (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_52EA1F099EEA759 ON order_item (inventory_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT FK_52EA1F099EEA759');
        $this->addSql('DROP INDEX IDX_52EA1F099EEA759');
        $this->addSql('ALTER TABLE order_item DROP unit_price');
        $this->addSql('ALTER TABLE order_item RENAME COLUMN inventory_id TO product_id');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT fk_52ea1f094584665a FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_52ea1f094584665a ON order_item (product_id)');
    }
}
