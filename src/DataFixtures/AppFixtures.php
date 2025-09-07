<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\Post;
use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    private function imm(?\DateTimeInterface $dt): ?\DateTimeImmutable
    {
        if ($dt === null) return null;
        return $dt instanceof \DateTimeImmutable ? $dt : \DateTimeImmutable::createFromMutable($dt);
    }

    public function load(ObjectManager $om): void
    {
        $faker = Factory::create('fr_FR');

        // USERS
        $admin = (new User())
            ->setEmail('admin@example.com')
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin'));
        $om->persist($admin);

        $demo = (new User())
            ->setEmail('demo@example.com')
            ->setRoles(['ROLE_USER']);
        $demo->setPassword($this->hasher->hashPassword($demo, 'demo'));
        $om->persist($demo);

        // CATEGORIES
        $catNames = [
            'Shonen','Shojo','Seinen','Josei','Isekai / Fantasy','Sports',
            'Classiques','Nouveautés 2025','Top / Classements','Adaptations Anime',
        ];
        $catsBySlug = [];
        foreach ($catNames as $name) {
            $c = (new Category())
                ->setName($name)
                ->setSlug($this->slugify($name));
            $om->persist($c);
            $catsBySlug[$c->getSlug()] = $c;
        }

        // TAGS
        $tagNames = [
            'manga','anime','top 10','2025','action','aventure','romance','drame','comédie','psychologie',
            'fantasy','autre monde','réincarnation','school life','sports','classiques',
            'one piece','jujutsu kaisen','my hero academia','chainsaw man','boruto','blue lock',
            'tokyo revengers','black clover','kaiju no 8','mashle',
            'fruits basket','yona','a sign of affection','skip beat','ao haru ride','orange',
            'kamisama kiss','my little monster','lovely complex',
            'berserk','vagabond','oshi no ko','pluto','tokyo ghoul','the fable',
            'dead dead demons','monster','gantz','hells paradise',
            'nana','chihayafuru','paradise kiss','midnight secretary','kuragehime','honey and clover',
            'helter skelter','descending stories','with the light',
            'mushoku tensei','re zero','tensei slime','sword art online','konosuba',
            'no game no life','overlord','shield hero','cautious hero',
        ];
        $tagsBySlug = [];
        foreach ($tagNames as $name) {
            $t = (new Tag())->setName($name)->setSlug($this->slugify($name));
            $om->persist($t);
            $tagsBySlug[$t->getSlug()] = $t;
        }

        // POSTS "TOP 10" FIXES
        $covers = [
            'shonen'  => 'https://cdn.oneesports.gg/cdn-data/2022/05/Anime_10bestshonen.jpg',
            'shojo'   => 'https://i.imgur.com/2l8k3tC.jpeg',
            'seinen'  => 'https://i.imgur.com/Mk0h4Qy.jpeg',
            'josei'   => 'https://i.imgur.com/8t7S6oJ.jpeg',
            'isekai'  => 'https://i.imgur.com/7m1xw0J.jpeg',
            'fallback'=> 'https://i.imgur.com/y9k6F9M.jpeg',
        ];

        $fixedPosts = [
            [
                'title' => 'Top 10 Shonen 2025',
                'category' => 'shonen',
                'cover' => $covers['shonen'],
                'content' => <<<MD
# 🔥 Top 10 Shonen 2025 🔥
Les mangas qui font vibrer la planète en 2025 : combats épiques, émotions fortes et univers explosifs !
...
MD,
                'tags' => ['manga','shonen','top 10','2025','action','aventure','jujutsu kaisen','one piece','my hero academia','chainsaw man','blue lock'],
            ],
            [
                'title' => 'Top 10 Shojo 2025',
                'category' => 'shojo',
                'cover' => $covers['shojo'],
                'content' => <<<MD
# 💖 Top 10 Shojo 2025 💖
...
MD,
                'tags' => ['manga','shojo','top 10','2025','romance','school life','fruits basket','yona','a sign of affection','skip beat','orange'],
            ],
            [
                'title' => 'Top 10 Seinen 2025',
                'category' => 'seinen',
                'cover' => $covers['seinen'],
                'content' => <<<MD
# ⚔️ Top 10 Seinen 2025 ⚔️
...
MD,
                'tags' => ['manga','seinen','top 10','2025','drame','action','psychologie','berserk','vagabond','pluto','monster'],
            ],
            [
                'title' => 'Top 10 Josei 2025',
                'category' => 'josei',
                'cover' => $covers['josei'],
                'content' => <<<MD
# 💕 Top 10 Josei 2025 💕
...
MD,
                'tags' => ['manga','josei','top 10','2025','romance','drame','nana','chihayafuru','paradise kiss','kuragehime'],
            ],
            [
                'title' => 'Top 10 Isekai 2025',
                'category' => 'isekai / fantasy',
                'cover' => $covers['isekai'],
                'content' => <<<MD
# 🌌 Top 10 Isekai 2025 🌌
...
MD,
                'tags' => ['manga','isekai','top 10','2025','fantasy','autre monde','réincarnation','mushoku tensei','re zero','tensei slime','sword art online','overlord'],
            ],
        ];

        foreach ($fixedPosts as $data) {
            $catSlug = $this->slugify($data['category']);
            $category = $catsBySlug[$catSlug] ?? reset($catsBySlug);

            $p = (new Post())
                ->setAuthor($admin)
                ->setTitle($data['title'])
                ->setSlug($this->slugify($data['title']))
                ->setContent($data['content'])
                ->setCover($data['cover'] ?? $covers['fallback'])
                ->setCategory($category)
                ->setStatus('published')
                ->setPublishedAt($this->imm($faker->dateTimeBetween('-15 days', 'now')));

            if (method_exists($p, 'setRating')) {
                $p->setRating($faker->numberBetween(4, 5));
            }

            $tagSlugs = array_map([$this, 'slugify'], $data['tags']);
            foreach ($tagSlugs as $slug) {
                if (isset($tagsBySlug[$slug])) {
                    $p->addTag($tagsBySlug[$slug]);
                }
            }

            $om->persist($p);

            // 1–3 commentaires approuvés
            for ($i=0; $i<$faker->numberBetween(1,3); $i++) {
                $c = (new Comment())
                    ->setAuthor($i % 2 ? $demo : $admin)
                    ->setPost($p)
                    ->setContent($faker->sentences($faker->numberBetween(1,2), true))
                    ->setStatus('approved')
                    ->setCreatedAt($this->imm($faker->dateTimeBetween('-10 days', 'now')));
                $om->persist($c);
            }
        }

        // POSTS ALÉATOIRES
        $allCats = array_values($catsBySlug);
        $allTags = array_values($tagsBySlug);

        for ($i = 0; $i < 10; $i++) {
            $title = $faker->sentence(3);
            $p = (new Post())
                ->setAuthor($faker->boolean(70) ? $admin : $demo)
                ->setTitle($title)
                ->setSlug($this->slugify($title))
                ->setContent($faker->paragraphs(mt_rand(3,7), true))
                ->setCover($covers['fallback'])
                ->setCategory($allCats[array_rand($allCats)])
                ->setStatus($faker->boolean(75) ? 'published' : 'draft');

            if ($p->getStatus() === 'published') {
                $p->setPublishedAt($this->imm($faker->dateTimeBetween('-6 months', 'now')));
            } else {
                $p->setPublishedAt(null);
            }

            if (method_exists($p, 'setRating')) {
                $p->setRating($faker->numberBetween(3,5));
            }

            shuffle($allTags);
            foreach (array_slice($allTags, 0, mt_rand(1,5)) as $t) {
                $p->addTag($t);
            }

            $om->persist($p);

            // 0 à 3 commentaires
            for ($k=0; $k<mt_rand(0,3); $k++) {
                $c = (new Comment())
                    ->setAuthor($k%2 ? $demo : $admin)
                    ->setPost($p)
                    ->setContent($faker->sentences(mt_rand(1,2), true))
                    ->setStatus(mt_rand(1,100) <= 70 ? 'approved' : 'pending')
                    ->setCreatedAt($this->imm($faker->dateTimeBetween('-3 months', 'now')));
                $om->persist($c);
            }
        }

        $om->flush();
    }

    private function slugify(string $str): string
    {
        $str = iconv('UTF-8','ASCII//TRANSLIT',$str);
        $str = preg_replace('/[^a-zA-Z0-9]+/', '-', $str);
        return strtolower(trim($str, '-'));
    }
}
