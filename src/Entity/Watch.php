<?php

namespace App\Entity;

use App\Repository\WatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WatchRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_user_post', columns: ['user_id','post_id'])]
class Watch
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'watches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    #[ORM\Column] private \DateTimeImmutable $createdAt;

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): self { $this->user=$u; return $this; }
    public function getPost(): ?Post { return $this->post; }
    public function setPost(?Post $p): self { $this->post=$p; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $d): self { $this->createdAt=$d; return $this; }
}
