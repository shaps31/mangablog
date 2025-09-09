<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'manga_release',
    indexes: [
        new ORM\Index(name: 'idx_release_date', columns: ['release_at']),
        new ORM\Index(name: 'idx_release_manga', columns: ['manga_id']),
    ],
)]
class Release
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 160)]
    private string $title;

    #[ORM\Column(name: 'release_at', type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $releaseAt;

    #[ORM\ManyToOne(targetEntity: Manga::class)]
    #[ORM\JoinColumn(name: 'manga_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Manga $manga = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $link = null;

    // -----------------
    // Getters / Setters
    // -----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getReleaseAt(): \DateTimeImmutable
    {
        return $this->releaseAt;
    }
    public function setReleaseAt(\DateTimeImmutable $releaseAt): self
    {
        $this->releaseAt = $releaseAt;
        return $this;
    }

    public function getManga(): ?Manga
    {
        return $this->manga;
    }
    public function setManga(?Manga $manga): self
    {
        $this->manga = $manga;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }
    public function setLink(?string $link): self
    {
        $this->link = $link;
        return $this;
    }

    // -----------------
    // Helpers pratiques
    // -----------------

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->title, $this->releaseAt->format('Y-m-d'));
    }

    public function isFuture(): bool
    {
        return $this->releaseAt > new \DateTimeImmutable();
    }
}
