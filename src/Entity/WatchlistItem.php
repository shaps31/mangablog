<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\Table(
    name: 'watchlist_item',
    uniqueConstraints: [
        // Contrainte DB : un même user ne peut pas ajouter 2x le même post
        new ORM\UniqueConstraint(name: 'uniq_user_post', columns: ['user_id', 'post_id'])
    ]
)]
#[UniqueEntity(fields: ['user', 'post'], message: 'Cet élément est déjà dans votre watchlist.')]
class WatchlistItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Assure-toi que User possède bien:
    // #[ORM\OneToMany(mappedBy: 'user', targetEntity: WatchlistItem::class)]
    // private Collection $watchlist;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'watchlist')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    // -----------------
    // Getters / Setters
    // -----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(Post $post): self
    {
        $this->post = $post;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
