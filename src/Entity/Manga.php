<?php
// src/Entity/Manga.php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'manga')]
class Manga
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 160)]
    private string $title;

    // Unicité DB + validation
    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Slug invalide. Utilise des minuscules, chiffres et tirets.')]
    private string $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $synopsis = null;

    // Ex: "Auteur1; Auteur2"
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $authors = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $coverUrl = null;

    // 'ongoing' | 'completed' (optionnel)
    #[ORM\Column(length: 32, nullable: true)]
    #[Assert\Choice(choices: ['ongoing', 'completed'])]
    private ?string $status = null;

    // Exemple: {"read": "...", "buy": "..."}
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $links = null;

    // Réutilise tes tags existants si tu veux
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private Collection $tags;

    // Rattache tes articles (posts) si tu veux afficher “Apparaît dans”
    #[ORM\ManyToMany(targetEntity: Post::class)]
    private Collection $appearsIn;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $featuredUntil = null;




    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->appearsIn = new ArrayCollection();
    }

    // -------------------------
    // Getters / Setters simples
    // -------------------------

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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSynopsis(): ?string
    {
        return $this->synopsis;
    }

    public function setSynopsis(?string $synopsis): self
    {
        $this->synopsis = $synopsis;
        return $this;
    }

    public function getAuthors(): ?string
    {
        return $this->authors;
    }

    public function setAuthors(?string $authors): self
    {
        $this->authors = $authors;
        return $this;
    }

    public function getFeaturedUntil(): ?\DateTimeImmutable { return $this->featuredUntil; }
    public function setFeaturedUntil(?\DateTimeImmutable $d): self { $this->featuredUntil = $d; return $this; }
    /**
     * Retourne la liste des auteurs (séparés par ";").
     * @return string[]
     */
    public function getAuthorsList(): array
    {
        if (!$this->authors) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(';', $this->authors))));
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(?string $coverUrl): self
    {
        $this->coverUrl = $coverUrl;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status ? strtolower($status) : null;
        return $this;
    }

    public function getLinks(): array
    {
        return $this->links ?? [];
    }

    public function setLinks(?array $links): self
    {
        $this->links = $links;
        return $this;
    }

    // ---------------
    // Relations Tags
    // ---------------

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    // ------------------
    // Relations Post(s)
    // ------------------

    /**
     * @return Collection<int, Post>
     */
    public function getAppearsIn(): Collection
    {
        return $this->appearsIn;
    }

    public function addAppearsIn(Post $post): self
    {
        if (!$this->appearsIn->contains($post)) {
            $this->appearsIn->add($post);
        }
        return $this;
    }

    public function removeAppearsIn(Post $post): self
    {
        $this->appearsIn->removeElement($post);
        return $this;
    }
}
