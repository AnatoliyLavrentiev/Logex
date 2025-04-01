<?php
// src/Entity/Configuration.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Configuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:255)]
    private ?string $nomDuSite = null;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $description = null;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $emailContact = null;

    #[ORM\Column(type:"boolean")]
    private bool $modeMaintenance = false;

    // Champs supplémentaires pour le SEO
    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $metaKeywords = null;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $metaDescription = null;

    // Champs pour les réseaux sociaux
    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $facebookUrl = null;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $twitterUrl = null;

    // Intégration de Google Analytics
    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $googleAnalyticsId = null;

    // Favicon
    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $faviconUrl = null;

    // Getters et Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomDuSite(): ?string
    {
        return $this->nomDuSite;
    }

    public function setNomDuSite(string $nomDuSite): self
    {
        $this->nomDuSite = $nomDuSite;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getEmailContact(): ?string
    {
        return $this->emailContact;
    }

    public function setEmailContact(string $emailContact): self
    {
        $this->emailContact = $emailContact;
        return $this;
    }

    public function getModeMaintenance(): bool
    {
        return $this->modeMaintenance;
    }

    public function setModeMaintenance(bool $modeMaintenance): self
    {
        $this->modeMaintenance = $modeMaintenance;
        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?string $metaKeywords): self
    {
        $this->metaKeywords = $metaKeywords;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function setFacebookUrl(?string $facebookUrl): self
    {
        $this->facebookUrl = $facebookUrl;
        return $this;
    }

    public function getTwitterUrl(): ?string
    {
        return $this->twitterUrl;
    }

    public function setTwitterUrl(?string $twitterUrl): self
    {
        $this->twitterUrl = $twitterUrl;
        return $this;
    }

    public function getGoogleAnalyticsId(): ?string
    {
        return $this->googleAnalyticsId;
    }

    public function setGoogleAnalyticsId(?string $googleAnalyticsId): self
    {
        $this->googleAnalyticsId = $googleAnalyticsId;
        return $this;
    }

    public function getFaviconUrl(): ?string
    {
        return $this->faviconUrl;
    }

    public function setFaviconUrl(?string $faviconUrl): self
    {
        $this->faviconUrl = $faviconUrl;
        return $this;
    }
}
