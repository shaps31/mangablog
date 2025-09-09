<?php



namespace App\Controller\Admin;

use App\Repository\PostRepository;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        PostRepository         $posts,
        CategoryRepository     $categories,
        TagRepository          $tags,
        CommentRepository      $comments,
        EntityManagerInterface $em,
    ): Response {
        // ----- Compteurs globaux
        $postCount     = $posts->count([]);
        $categoryCount = $categories->count([]);
        $tagCount      = $tags->count([]);
        $commentCount  = $comments->count([]);

        // ----- 5 derniers articles publiés
        $latestPosts = $posts->createQueryBuilder('p')
            ->andWhere('p.status = :s')->setParameter('s', 'published')
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // ----- 5 commentaires en attente
        $pendingComments = $comments->findBy(
            ['status' => 'pending'],
            ['createdAt' => 'DESC'],
            5
        );

        // ----- Top 5 catégories (nb d’articles publiés)
        $topCategories = $em->createQuery(
            'SELECT c.name AS name, COUNT(p.id) AS total
             FROM App\Entity\Post p
             JOIN p.category c
             WHERE p.status = :s
             GROUP BY c.id, c.name
             ORDER BY total DESC'
        )
            ->setParameter('s', 'published')
            ->setMaxResults(5)
            ->getResult();

        // ----- Nombre d’articles publiés ce mois-ci
        $from = new \DateTimeImmutable('first day of this month 00:00:00');
        $to   = $from->modify('first day of next month 00:00:00');

        $publishedThisMonth = (int) $posts->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :s')->setParameter('s', 'published')
            ->andWhere('p.publishedAt >= :from')->setParameter('from', $from)
            ->andWhere('p.publishedAt < :to')->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();

        // ======= 1) Articles publiés par mois (année courante) — SQL natif selon la plateforme
        $year  = (int) (new \DateTime())->format('Y');
        $start = new \DateTimeImmutable("$year-01-01 00:00:00");
        $end   = new \DateTimeImmutable("$year-12-31 23:59:59");

        $conn      = $em->getConnection();
        $platform  = $conn->getDatabasePlatform()->getName(); // 'sqlite' | 'mysql' | 'postgresql'
        // Attention: noms de tables/colonnes = mapping par défaut (snake_case)
        // Table: post ; colonnes: id, status, published_at
        if ($platform === 'sqlite') {
            $sql = "
                SELECT CAST(strftime('%m', p.published_at) AS INTEGER) AS m, COUNT(p.id) AS c
                FROM post p
                WHERE p.status = :s
                  AND p.published_at BETWEEN :start AND :end
                GROUP BY m
            ";
        } elseif ($platform === 'mysql') {
            $sql = "
                SELECT MONTH(p.published_at) AS m, COUNT(p.id) AS c
                FROM post p
                WHERE p.status = :s
                  AND p.published_at BETWEEN :start AND :end
                GROUP BY m
            ";
        } else { // postgresql
            $sql = "
                SELECT EXTRACT(MONTH FROM p.published_at)::int AS m, COUNT(p.id) AS c
                FROM post p
                WHERE p.status = :s
                  AND p.published_at BETWEEN :start AND :end
                GROUP BY m
            ";
        }

        $rows = $conn->executeQuery($sql, [
            's'     => 'published',
            'start' => $start->format('Y-m-d H:i:s'),
            'end'   => $end->format('Y-m-d H:i:s'),
        ])->fetchAllAssociative();

        // Normalisation en tableau indexé 1..12
        $perMonth = array_fill(1, 12, 0);
        foreach ($rows as $r) {
            // SQLite renvoie déjà 1..12 (cast), MySQL/PG idem
            $m = (int) $r['m'];
            if ($m >= 1 && $m <= 12) {
                $perMonth[$m] = (int) $r['c'];
            }
        }

        // ======= 2) Top tags (par nb d’articles publiés) — DQL ok
        $topTags = $em->createQueryBuilder()
            ->select('t.name AS name, COUNT(p.id) AS c')
            ->from(\App\Entity\Tag::class, 't')
            ->join('t.posts', 'p')
            ->where('p.status = :s')
            ->setParameter('s', 'published')
            ->groupBy('t.id')
            ->orderBy('c', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getArrayResult();

        // ----- Rendu
        return $this->render('admin/dashboard.html.twig', [
            // Compteurs (utilisés dans les cartes)
            'counts' => [
                'posts'      => $postCount,
                'categories' => $categoryCount,
                'tags'       => $tagCount,
                'comments'   => $commentCount,
            ],

            // Sections du tableau de bord
            'latestPosts'        => $latestPosts,
            'pendingComments'    => $pendingComments,
            'topCategories'      => $topCategories,
            'publishedThisMonth' => $publishedThisMonth,

            // Alias déjà utilisé dans ton Twig
            'pending'            => $pendingComments,

            // Nouveaux datasets (graphiques, etc.)
            'perMonth'           => $perMonth,  // ex: {1:3, 2:5, ... 12:0}
            'topTags'            => $topTags,   // ex: [{name:'Shonen', c:12}, ...]
        ]);
    }
}
