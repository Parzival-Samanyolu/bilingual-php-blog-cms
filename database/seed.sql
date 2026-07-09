-- ============================================================================
-- real.com.tr — Seed Data
-- Run AFTER schema.sql. Uses explicit IDs so foreign keys resolve reliably.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- Admin user
-- Plaintext password: Admin123!
-- The password_hash below is a valid PHP password_hash('Admin123!', PASSWORD_ARGON2ID) literal.
-- To regenerate on the server run:
--   php -r "echo password_hash('Admin123!', PASSWORD_ARGON2ID), PHP_EOL;"
-- then replace the value below with the new hash (each run produces a different salt).
-- ----------------------------------------------------------------------------
INSERT INTO `users`
  (`id`, `name`, `username`, `email`, `password_hash`, `role`, `is_approved`, `email_verified_at`, `preferred_lang`)
VALUES
  (1, 'Admin', 'admin', 'admin@real.com.tr',
   '$argon2id$v=19$m=65536,t=4,p=1$aENkMDAzbmJSTFhiWVdjRA$dkUWHST0iujlm1I3JPMvICKzrHS8QcYvpHYImpQVteE',
   'admin', 1, NOW(), 'tr');

-- ----------------------------------------------------------------------------
-- Categories (2 roots + 2 subcategories each)
-- ----------------------------------------------------------------------------
INSERT INTO `categories` (`id`, `parent_id`, `slug`, `sort_order`) VALUES
  (1, NULL, 'teknoloji',      1),
  (2, NULL, 'tarih',          2),
  (3, 1,    'yazilim',        1),
  (4, 1,    'yapay-zeka',     2),
  (5, 2,    'antik-caglar',   1),
  (6, 2,    'modern-tarih',   2);

INSERT INTO `category_translations` (`category_id`, `lang`, `name`, `description`) VALUES
  (1, 'tr', 'Teknoloji',       'Teknoloji dünyasından haberler, rehberler ve analizler.'),
  (1, 'en', 'Technology',      'News, guides and analysis from the world of technology.'),
  (2, 'tr', 'Tarih',           'Geçmişten günümüze tarihsel olaylar ve dönemler.'),
  (2, 'en', 'History',         'Historical events and eras from the past to the present.'),
  (3, 'tr', 'Yazılım',         'Programlama dilleri, araçlar ve yazılım geliştirme.'),
  (3, 'en', 'Software',        'Programming languages, tools and software development.'),
  (4, 'tr', 'Yapay Zeka',      'Yapay zeka, makine öğrenmesi ve derin öğrenme konuları.'),
  (4, 'en', 'Artificial Intelligence', 'Artificial intelligence, machine learning and deep learning topics.'),
  (5, 'tr', 'Antik Çağlar',    'Antik uygarlıklar ve eski dünya tarihi.'),
  (5, 'en', 'Ancient Times',   'Ancient civilizations and the history of the old world.'),
  (6, 'tr', 'Modern Tarih',    'Yakın dönem tarihi ve çağdaş olaylar.'),
  (6, 'en', 'Modern History',  'Recent history and contemporary events.');

-- ----------------------------------------------------------------------------
-- Tags
-- ----------------------------------------------------------------------------
INSERT INTO `tags` (`id`, `slug`) VALUES
  (1, 'php'),
  (2, 'yapay-zeka'),
  (3, 'osmanli');

INSERT INTO `tag_translations` (`tag_id`, `lang`, `name`) VALUES
  (1, 'tr', 'PHP'),            (1, 'en', 'PHP'),
  (2, 'tr', 'Yapay Zeka'),     (2, 'en', 'Artificial Intelligence'),
  (3, 'tr', 'Osmanlı'),        (3, 'en', 'Ottoman');

-- ----------------------------------------------------------------------------
-- Articles (3 published, TR, authored by admin)
-- ----------------------------------------------------------------------------
INSERT INTO `articles`
  (`id`, `author_id`, `category_id`, `slug`, `cover_image`, `status`, `view_count`, `lang`, `translation_of`)
VALUES
  (1, 1, 3, 'php-8-2-ile-gelen-yenilikler',        NULL, 'published', 0, 'tr', NULL),
  (2, 1, 4, 'yapay-zekanin-gunluk-hayata-etkileri', NULL, 'published', 0, 'tr', NULL),
  (3, 1, 5, 'antik-misir-uygarligi',               NULL, 'published', 0, 'tr', NULL);

INSERT INTO `article_translations`
  (`article_id`, `lang`, `title`, `content`, `excerpt`, `meta_title`, `meta_description`)
VALUES
  (1, 'tr',
   'PHP 8.2 ile Gelen Yenilikler',
   '<h2>PHP 8.2 Genel Bakış</h2><p>PHP 8.2 sürümü, dile birçok yeni özellik ve performans iyileştirmesi getirdi. Bu yazıda öne çıkan yenilikleri inceliyoruz.</p><h3>Readonly Sınıflar</h3><p>Artık tüm sınıfı <code>readonly</code> olarak işaretleyebilir ve tüm özelliklerin değişmez olmasını sağlayabilirsiniz.</p><h3>Yeni Tipler</h3><p><code>null</code>, <code>true</code> ve <code>false</code> artık bağımsız tipler olarak kullanılabiliyor.</p><p>Bu yenilikler daha güvenli ve okunabilir kod yazmayı kolaylaştırıyor.</p>',
   'PHP 8.2 sürümünün getirdiği readonly sınıflar, yeni tipler ve performans iyileştirmelerine kısa bir bakış.',
   'PHP 8.2 ile Gelen Yenilikler',
   'PHP 8.2 sürümünün getirdiği readonly sınıflar, yeni tipler ve önemli yenilikleri keşfedin.'),
  (2, 'tr',
   'Yapay Zekanın Günlük Hayata Etkileri',
   '<h2>Günlük Hayatta Yapay Zeka</h2><p>Yapay zeka teknolojileri artık hayatımızın her alanında karşımıza çıkıyor. Telefonlarımızdaki sesli asistanlardan öneri sistemlerine kadar pek çok yerde yapay zeka çalışıyor.</p><h3>Sağlık</h3><p>Yapay zeka, hastalıkların erken teşhisinde ve tedavi süreçlerinin planlanmasında önemli rol oynuyor.</p><h3>Ulaşım</h3><p>Otonom araçlar ve akıllı trafik sistemleri şehirleri dönüştürüyor.</p><p>Gelecekte yapay zekanın etkisi daha da artacak.</p>',
   'Yapay zekanın sağlıktan ulaşıma kadar günlük hayatımızı nasıl dönüştürdüğüne dair bir değerlendirme.',
   'Yapay Zekanın Günlük Hayata Etkileri',
   'Yapay zekanın sağlık, ulaşım ve günlük yaşamdaki dönüştürücü etkilerini inceleyin.'),
  (3, 'tr',
   'Antik Mısır Uygarlığı',
   '<h2>Nil’in Armağanı</h2><p>Antik Mısır, insanlık tarihinin en köklü ve etkileyici uygarlıklarından biridir. Nil Nehri çevresinde binlerce yıl boyunca gelişen bu medeniyet, mimari ve bilim alanında çığır açtı.</p><h3>Piramitler</h3><p>Giza piramitleri, dönemin mühendislik dehasını gözler önüne seriyor.</p><h3>Hiyeroglif Yazısı</h3><p>Mısırlılar, karmaşık bir yazı sistemi geliştirerek bilgiyi nesilden nesile aktardı.</p><p>Antik Mısır’ın mirası bugün hâlâ araştırmacıları büyülemeye devam ediyor.</p>',
   'Nil Nehri kıyısında yükselen Antik Mısır uygarlığının piramitleri, yazısı ve kalıcı mirası.',
   'Antik Mısır Uygarlığı',
   'Antik Mısır uygarlığının piramitlerini, hiyeroglif yazısını ve kalıcı mirasını keşfedin.');

-- ----------------------------------------------------------------------------
-- Article <-> Tag links
-- ----------------------------------------------------------------------------
INSERT INTO `article_tags` (`article_id`, `tag_id`) VALUES
  (1, 1),  -- PHP article  -> PHP tag
  (2, 2),  -- AI article   -> AI tag
  (3, 3);  -- Egypt article-> Ottoman/history tag
