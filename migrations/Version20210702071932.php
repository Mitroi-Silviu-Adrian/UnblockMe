<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210702071932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE license_plates ADD CONSTRAINT FK_9D28B7A69D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX idx_f5aa79d09d86650f ON license_plates');
        $this->addSql('CREATE INDEX IDX_9D28B7A69D86650F ON license_plates (user_id_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license_plates DROP FOREIGN KEY FK_9D28B7A69D86650F');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE license_plates DROP FOREIGN KEY FK_9D28B7A69D86650F');
        $this->addSql('DROP INDEX idx_9d28b7a69d86650f ON license_plates');
        $this->addSql('CREATE INDEX IDX_F5AA79D09D86650F ON license_plates (user_id_id)');
        $this->addSql('ALTER TABLE license_plates ADD CONSTRAINT FK_9D28B7A69D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
    }
}
