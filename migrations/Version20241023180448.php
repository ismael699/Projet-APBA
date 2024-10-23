<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241023180448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cause DROP FOREIGN KEY FK_F0DA7FBFA76ED395');
        $this->addSql('DROP INDEX IDX_F0DA7FBFA76ED395 ON cause');
        $this->addSql('ALTER TABLE cause DROP user_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cause ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cause ADD CONSTRAINT FK_F0DA7FBFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F0DA7FBFA76ED395 ON cause (user_id)');
    }
}
