<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210721064243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity CHANGE status status INT NOT NULL');
        $this->addSql('ALTER TABLE license_plates DROP FOREIGN KEY FK_9D28B7A69D86650F');
        $this->addSql('DROP INDEX idx_f5aa79d09d86650f ON license_plates');
        $this->addSql('CREATE INDEX IDX_9D28B7A69D86650F ON license_plates (user_id_id)');
        $this->addSql('ALTER TABLE license_plates ADD CONSTRAINT FK_9D28B7A69D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD username VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity CHANGE status status INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE license_plates DROP FOREIGN KEY FK_9D28B7A69D86650F');
        $this->addSql('DROP INDEX idx_9d28b7a69d86650f ON license_plates');
        $this->addSql('CREATE INDEX IDX_F5AA79D09D86650F ON license_plates (user_id_id)');
        $this->addSql('ALTER TABLE license_plates ADD CONSTRAINT FK_9D28B7A69D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user DROP username');
    }
}
