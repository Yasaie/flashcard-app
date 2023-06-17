# Flashcard App

This is an interactive CLI program for Flashcard practice, built with Laravel.

## Project setup

1. Clone the repository to your local machine.

2. Install the project dependencies using Composer:

    ```bash
    composer install
    ```

3. Configure your database settings in the `.env` file.

4. Run the database migrations to set up the required tables:

    ```bash
    php artisan migrate
    ```

## How to run the project

To start the Flashcard App using Laravel Sail, follow these steps:

- Ensure that Docker is installed on your machine.

- Build and start the Sail containers:

    ```bash
    ./vendor/bin/sail up -d
    ```

- Access the workspace container:

    ``` bash
    ./vendor/bin/sail shell
    ````

- Run the following command to start the Flashcard App:

    ```bash
    php artisan flashcard:interactive
    ```

  Follow the on-screen menu options to create flashcards, list flashcards, practice flashcards, view stats, reset
  progress, or exit the program.

## Database structure

The Flashcard App uses a SQL database to store flashcards and user progress. The structure of the relevant tables is as
follows:

### Flashcards table

The `flashcards` table stores the flashcard questions and answers.

| Column     | Type      | Description           |
|------------|-----------|-----------------------|
| id         | integer   | Unique identifier     |
| question   | string    | Flashcard question    |
| answer     | string    | Flashcard answer      |
| created_at | timestamp | Creation timestamp    |
| updated_at | timestamp | Last update timestamp |

### Flashcard progress table

The `flashcard_progress` table tracks the progress of each flashcard for each user.

| Column       | Type         | Description                                        |
|--------------|--------------|----------------------------------------------------|
| id           | integer      | Unique identifier                                  |
| flashcard_id | foreign key  | Reference to the `flashcards` table                |
| username     | string       | User's name                                        |
| status       | tiny integer | Status of the flashcard (1: Correct, 2: Incorrect) |
| created_at   | timestamp    | Creation timestamp                                 |
| updated_at   | timestamp    | Last update timestamp                              |

The `flashcard_progress` table has a unique constraint on the combination of `flashcard_id` and `username`, ensuring
that a user can have only one progress record for each flashcard.

## Justification and dependencies

- Laravel Framework: The project leverages the Laravel framework's features and conventions to streamline development and enhance code organization.

- Artisan Console: The project utilizes Laravel's Artisan console, a powerful command-line interface, to create the interactive `flashcard:interactive` command and handle user interactions.

- Composer: The project uses Composer as a dependency manager to install and manage PHP packages required for the project.

- Laravel Sail: The Flashcard App supports Laravel Sail, which provides a lightweight Docker-powered development environment. It simplifies the setup process and ensures consistency across different development environments.

- Git: The project is version-controlled with Git, allowing for efficient collaboration, code management, and easy deployment to various hosting platforms.

> Please ensure you have PHP, Composer, Docker (for Laravel Sail), and Git installed on your system before running the project.
