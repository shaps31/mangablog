<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'author')]
    private Collection $posts;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'author')]
    private Collection $comments;

    // --- Champs profil ---

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    // Champ standard pour l'avatar (URL absolue ou chemin relatif public/)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarUrl = null;

    // Champ historique (legacy) conservé pour compatibilité avec d’anciens formulaires/templates.
    // On le synchronise avec avatarUrl via les alias getAvatar()/setAvatar().
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    // ---------------------------------------------------------
    // Identité / sécurité
    // ---------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them (Symfony 7.3+).
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        if ($this->password !== null) {
            $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        }
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, kept for BC. No sensitive temp data stored.
    }

    // ---------------------------------------------------------
    // Relations
    // ---------------------------------------------------------

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthor($this);
        }
        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setAuthor($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
            }
        }
        return $this;
    }

    // ---------------------------------------------------------
    // Profil
    // ---------------------------------------------------------

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $v): self { $this->firstName = $v; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $v): self { $this->lastName = $v; return $this; }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $v): self { $this->bio = $v; return $this; }

    public function getAvatarUrl(): ?string { return $this->avatarUrl; }
    public function setAvatarUrl(?string $u): self
    {
        $this->avatarUrl = $u;
        // Synchronise le champ legacy pour éviter toute divergence
        $this->avatar = $u;
        return $this;
    }

    /**
     * Alias legacy : certains vieux formulaires/templates utilisent encore "avatar".
     * On mappe donc sur avatarUrl pour rester cohérent.
     */
    public function getAvatar(): ?string
    {
        return $this->avatarUrl ?? $this->avatar;
    }

    public function setAvatar(?string $url): self
    {
        $this->avatarUrl = $url;
        $this->avatar    = $url;
        return $this;
    }

    /**
     * Un nom d’affichage agréable :
     * 1) Prénom Nom
     * 2) préfixe d’email
     * 3) identifiant
     */
    public function getDisplayName(): string
    {
        $full = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
        if ($full !== '') {
            return $full;
        }

        $email = (string) ($this->email ?? '');
        if ($email !== '') {
            $local = preg_replace('/@.+$/', '', $email);
            if (!empty($local)) {
                return $local;
            }
        }

        return (string) $this->getUserIdentifier();
    }

    /**
     * Renvoie une image exploitable :
     * - URL http(s) si fournie
     * - chemin relatif (public/) sinon
     * - Gravatar en dernier recours
     */
    public function getAvatarOrGravatar(): string
    {
        $src = $this->avatarUrl ?? $this->avatar;
        if ($src) {
            if (preg_match('~^https?://~i', $src)) {
                return $src;
            }
            return '/' . ltrim($src, '/');
        }

        $hash = md5(strtolower(trim((string) $this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=160";
    }

    /** Initiales si pas d’image utile (UI helpers) */
    public function getInitials(): string
    {
        $s = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
        if ($s !== '') {
            $parts = preg_split('/\s+/', $s);
            return strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
        }
        return strtoupper(mb_substr((string) $this->getUserIdentifier(), 0, 1));
    }
}
