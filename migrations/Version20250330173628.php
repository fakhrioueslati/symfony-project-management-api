<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250330173628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, role_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_64C19C1A76ED395 (user_id), INDEX IDX_64C19C1D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comments (id INT AUTO_INCREMENT NOT NULL, task_id INT NOT NULL, user_id INT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_5F9E962A8DB60186 (task_id), INDEX IDX_5F9E962AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE permission (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, action LONGTEXT DEFAULT NULL, entity VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, owner_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_2FB3D0EE12469DE2 (category_id), INDEX IDX_2FB3D0EE7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_assignment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, project_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_28633F88A76ED395 (user_id), INDEX IDX_28633F88166D1F9C (project_id), INDEX IDX_28633F88D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role_permission (role_id INT NOT NULL, permission_id INT NOT NULL, INDEX IDX_6F7DF886D60322AC (role_id), INDEX IDX_6F7DF886FED90CCA (permission_id), PRIMARY KEY(role_id, permission_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, status_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, INDEX IDX_527EDB25166D1F9C (project_id), INDEX IDX_527EDB256BF700BD (status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task_assignment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, task_id INT NOT NULL, INDEX IDX_2CD60F15A76ED395 (user_id), INDEX IDX_2CD60F158DB60186 (task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE project_assignment ADD CONSTRAINT FK_28633F88A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE project_assignment ADD CONSTRAINT FK_28633F88166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_assignment ADD CONSTRAINT FK_28633F88D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB256BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('ALTER TABLE task_assignment ADD CONSTRAINT FK_2CD60F15A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task_assignment ADD CONSTRAINT FK_2CD60F158DB60186 FOREIGN KEY (task_id) REFERENCES task (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1A76ED395');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1D60322AC');
        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962A8DB60186');
        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962AA76ED395');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE12469DE2');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE7E3C61F9');
        $this->addSql('ALTER TABLE project_assignment DROP FOREIGN KEY FK_28633F88A76ED395');
        $this->addSql('ALTER TABLE project_assignment DROP FOREIGN KEY FK_28633F88166D1F9C');
        $this->addSql('ALTER TABLE project_assignment DROP FOREIGN KEY FK_28633F88D60322AC');
        $this->addSql('ALTER TABLE role_permission DROP FOREIGN KEY FK_6F7DF886D60322AC');
        $this->addSql('ALTER TABLE role_permission DROP FOREIGN KEY FK_6F7DF886FED90CCA');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25166D1F9C');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB256BF700BD');
        $this->addSql('ALTER TABLE task_assignment DROP FOREIGN KEY FK_2CD60F15A76ED395');
        $this->addSql('ALTER TABLE task_assignment DROP FOREIGN KEY FK_2CD60F158DB60186');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE permission');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_assignment');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE role_permission');
        $this->addSql('DROP TABLE status');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE task_assignment');
        $this->addSql('DROP TABLE user');
    }
}
