<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Perfil do candidato — base para scoring de compatibilidade de vagas.
 * Skills organizadas por peso (1-10): quanto maior, mais central no perfil.
 *
 * SPEC-013
 */
final class ResumeProfile
{
    /**
     * Skills com peso de relevância (1–10).
     *
     * 10 = obrigatório / core
     * 7–9 = forte / frequente
     * 4–6 = complementar
     * 1–3 = periférico / mencionado
     */
    public const SKILLS = [
        // PHP ecosystem (core — 9 anos de experiência)
        'php'                => 10,
        'laravel'            => 10,
        'symfony'            => 8,

        // Node.js ecosystem
        'node.js'            => 9,
        'node'               => 9,
        'nodejs'             => 9,
        'express'            => 7,

        // Languages
        'typescript'         => 8,
        'javascript'         => 8,

        // Databases
        'mysql'              => 7,
        'postgresql'         => 7,
        'postgres'           => 7,
        'redis'              => 7,
        'mongodb'            => 5,
        'oracle'             => 5,
        'sql server'         => 4,

        // Messaging
        'rabbitmq'           => 7,
        'kafka'              => 7,
        'amazon mq'          => 5,
        'sqs'                => 6,

        // Cloud
        'aws'                => 8,
        'azure'              => 6,
        'lambda'             => 6,
        'ecs'                => 5,
        'docker'             => 8,
        'kubernetes'         => 6,
        'k8s'                => 6,

        // CI/CD
        'github actions'     => 6,
        'gitlab'             => 6,
        'ci/cd'              => 6,
        'devops'             => 6,

        // Architecture
        'microsserviços'     => 8,
        'microservices'      => 8,
        'api rest'           => 8,
        'rest api'           => 8,
        'restful'            => 7,
        'event-driven'       => 7,
        'event driven'       => 7,
        'serverless'         => 6,
        'bff'                => 5,
        'solid'              => 6,
        'clean architecture' => 6,
        'tdd'                => 6,

        // Frontend (complementar)
        'angular'            => 5,
        'vue'                => 5,
        'vue.js'             => 5,

        // Testing
        'phpunit'            => 5,
        'pest'               => 5,
        'vitest'             => 4,

        // Observability
        'sonarqube'          => 4,
        'elk'                => 4,
        'cloudwatch'         => 4,

        // AI/SDD (diferencial)
        'copilot'            => 4,
        'sdd'                => 4,

        // IoT
        'iot'                => 4,
    ];

    /**
     * Skills que desqualificam se encontradas no TÍTULO da vaga.
     * Vagas cujo cargo principal é baseado nessas tecnologias.
     */
    public const TITLE_DISQUALIFYING = [
        'python',
        'django',
        'flask',
        'fastapi',
        'ruby',
        'rails',
        'java ',
        'spring',
        'kotlin',
        '.net',
        'c#',
        'golang',
        ' go ',
        'rust',
        'react',
        'react.js',
        'reactjs',
        'next.js',
        'nextjs',
    ];

    /**
     * Frameworks/libs 100% exclusivos de ecossistemas fora do perfil.
     * Se aparecerem em QUALQUER parte do texto (título + descrição + requisitos),
     * a vaga é descartada — não há ambiguidade sobre a linguagem principal.
     *
     * Resolve o caso "Engenheiro de Backend" com Python/FastAPI na descrição
     * (caso real: Jobbol / Locus Software, set/2026).
     */
    public const FULLTEXT_DISQUALIFYING = [
        // Python-exclusivos
        'fastapi',
        'sqlalchemy',
        'alembic',
        'pydantic',
        'uvicorn',
        'starlette',
        'celery',
        'pytest',
        'tortoise-orm',
        'asyncpg',
        'django',
        'flask',

        // Java-exclusivos
        'spring boot',
        'spring mvc',
        'spring cloud',
        'hibernate',
        'jpa ',
        'maven',
        'gradle',
        'quarkus',
        'micronaut',
        ' jvm',

        // .NET-exclusivos
        'asp.net',
        'entity framework',
        'blazor',
        'nuget',

        // Ruby-exclusivos
        'activerecord',
        'sinatra',
        'rspec',
        'rubygems',

        // React ecosystem (não é Vue nem Angular)
        'react hooks',
        'react native',
        'next.js',
        'nextjs',
        'remix',
        'gatsby',
    ];

    /** Score mínimo (0–100) para uma vaga ser aceita no pipeline. */
    public const MIN_SCORE = 80;

    /**
     * Peso de referência para normalização do score.
     * Representa a soma das top-8 skills core do perfil:
     * php(10) + laravel(10) + node.js(9) + typescript(8) + aws(8) + docker(8) + mysql(7) + redis(7) = 67
     *
     * Uma vaga que menciona todas essas skills → score 100%.
     * Uma vaga que menciona php + laravel + docker + mysql + redis + rabbitmq → ~74% (FAIL, ok).
     * Uma vaga que menciona php + laravel + docker + mysql + redis + rabbitmq + node.js → ~88% (PASS).
     */
    public const SCORE_BASELINE = 67;
}
