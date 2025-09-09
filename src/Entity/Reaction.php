<?php


namespace App\Entity;

use App\Repository\ReactionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Post;
use App\Entity\User;

#[ORM\Entity(repositoryClass: ReactionRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_user_post_kind', fields: ['user', 'post', 'kind'])]
class Reaction
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    #[ORM\Column(length: 8)]
    private string $kind; // fire|lol|cry|wow

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $u): self
    {
        $this->user = $u;
        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(Post $p): self
    {
        $this->post = $p;
        return $this;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind(string $k): self
    {
        $this->kind = $k;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $d): self
    {
        $this->createdAt = $d;
        return $this;
    }
}
