<?php

declare(strict_types=1);

/**
 * Turkish (tr) UI strings for real.com.tr.
 *
 * Every key here MUST have an identical counterpart in lang/en.php.
 * Values may contain {placeholder} tokens replaced by __($key, ['placeholder' => ...]).
 *
 * @return array<string,string>
 */
return [
    // --- Branding ---------------------------------------------------------
    'site_name'          => 'real.com.tr',
    'site_tagline'       => 'Bilgi ve ansiklopedi platformu',

    // --- Navigation -------------------------------------------------------
    'nav_home'           => 'Ana Sayfa',
    'nav_search'         => 'Ara',
    'nav_login'          => 'Giriş Yap',
    'nav_register'       => 'Kayıt Ol',
    'nav_logout'         => 'Çıkış Yap',
    'nav_profile'        => 'Profilim',
    'nav_dashboard'      => 'Yazar Paneli',
    'nav_admin'          => 'Yönetim',
    'nav_categories'     => 'Kategoriler',
    'nav_about'          => 'Hakkımızda',
    'nav_contact'        => 'İletişim',
    'nav_menu'           => 'Menü',

    // --- Search -----------------------------------------------------------
    'search_placeholder' => 'Makale, konu veya anahtar kelime ara...',
    'search_results_for' => '"{query}" için arama sonuçları',
    'search_button'      => 'Ara',
    'no_results'         => 'Sonuç bulunamadı.',
    'no_results_hint'    => 'Farklı anahtar kelimeler deneyin veya filtreleri temizleyin.',
    'filter_category'    => 'Kategoriye göre filtrele',
    'filter_tag'         => 'Etikete göre filtrele',
    'filter_all'         => 'Tümü',
    'filter_apply'       => 'Uygula',
    'filter_clear'       => 'Filtreleri temizle',
    'results_count'      => '{count} sonuç',

    // --- Article actions --------------------------------------------------
    'btn_read_more'      => 'Devamını oku',
    'btn_like'           => 'Beğen',
    'btn_bookmark'       => 'Kaydet',
    'btn_share'          => 'Paylaş',
    'btn_liked'          => 'Beğenildi',
    'btn_bookmarked'     => 'Kaydedildi',
    'share_copy_link'    => 'Bağlantıyı kopyala',
    'share_copied'       => 'Bağlantı kopyalandı',
    'share_twitter'      => 'X\'te paylaş',
    'share_facebook'     => 'Facebook\'ta paylaş',
    'share_whatsapp'     => 'WhatsApp\'ta paylaş',

    // --- Article meta labels ---------------------------------------------
    'label_views'        => 'görüntülenme',
    'label_comments'     => 'yorum',
    'label_likes'        => 'beğeni',
    'label_author'       => 'Yazar',
    'label_date'         => 'Tarih',
    'label_category'     => 'Kategori',
    'label_tags'         => 'Etiketler',
    'label_updated'      => 'Güncelleme',
    'label_reading_time' => 'okuma süresi',
    'minutes_short'      => 'dk',

    // --- Related / listings ----------------------------------------------
    'related_articles'   => 'İlgili Makaleler',
    'latest_articles'    => 'En Yeni Makaleler',
    'trending'           => 'Öne Çıkanlar',
    'categories_heading' => 'Kategoriler',
    'tags_heading'       => 'Etiketler',
    'more_in_category'   => 'Bu kategoride daha fazlası',
    'view_all'           => 'Tümünü gör',

    // --- Comments ---------------------------------------------------------
    'comments_heading'   => 'Yorumlar',
    'leave_comment'      => 'Yorum yaz',
    'comment_placeholder' => 'Yorumunuzu yazın...',
    'comment_submit'     => 'Yorumu gönder',
    'comment_reply'      => 'Yanıtla',
    'comment_pending'    => 'Yorumunuz onay bekliyor.',
    'comment_success'    => 'Yorumunuz gönderildi ve onaylandıktan sonra yayınlanacak.',
    'login_to_comment'   => 'Yorum yapmak için giriş yapın.',
    'no_comments'        => 'Henüz yorum yok. İlk yorumu siz yazın.',

    // --- Pagination -------------------------------------------------------
    'pagination_prev'    => 'Önceki',
    'pagination_next'    => 'Sonraki',
    'pagination_page'    => 'Sayfa {page}',
    'pagination_of'      => '{total} sayfadan {current}. sayfa',

    // --- Auth: login / register ------------------------------------------
    'login_title'        => 'Giriş Yap',
    'register_title'     => 'Kayıt Ol',
    'label_name'         => 'Ad Soyad',
    'label_username'     => 'Kullanıcı adı',
    'label_email'        => 'E-posta',
    'label_password'     => 'Parola',
    'label_password_confirm' => 'Parola (tekrar)',
    'btn_login'          => 'Giriş yap',
    'btn_register'       => 'Kayıt ol',
    'remember_me'        => 'Beni hatırla',
    'forgot_password'    => 'Parolamı unuttum',
    'or_continue_with'   => 'veya şununla devam et',
    'login_with_google'  => 'Google ile giriş yap',
    'no_account'         => 'Hesabınız yok mu?',
    'have_account'       => 'Zaten hesabınız var mı?',
    'register_as_author' => 'Yazar olarak kayıt ol',
    'register_as_reader' => 'Okuyucu olarak kayıt ol',
    'register_role_hint' => 'Yazar hesapları yönetici onayından sonra makale yayınlayabilir.',
    'pending_approval_message' => 'Yazar başvurunuz alındı. Hesabınız yönetici tarafından onaylandığında bilgilendirileceksiniz.',

    // --- Profile ----------------------------------------------------------
    'profile_title'      => 'Profil',
    'profile_bio'        => 'Hakkında',
    'profile_articles'   => 'Makaleleri',
    'profile_bookmarks'  => 'Kaydedilenler',
    'profile_edit'       => 'Profili düzenle',
    'profile_avatar'     => 'Profil fotoğrafı',

    // --- Admin ------------------------------------------------------------
    'admin_dashboard'    => 'Kontrol Paneli',
    'admin_articles'     => 'Makaleler',
    'admin_categories'   => 'Kategoriler',
    'admin_users'        => 'Kullanıcılar',
    'admin_comments'     => 'Yorumlar',
    'admin_tags'         => 'Etiketler',
    'admin_settings'     => 'Ayarlar',
    'admin_pending_authors' => 'Onay Bekleyen Yazarlar',
    'admin_pending_comments' => 'Onay Bekleyen Yorumlar',
    'admin_stats'        => 'İstatistikler',

    // --- Status labels ----------------------------------------------------
    'status_draft'       => 'Taslak',
    'status_pending'     => 'Onay bekliyor',
    'status_published'   => 'Yayında',
    'status_rejected'    => 'Reddedildi',

    // --- Generic buttons --------------------------------------------------
    'btn_approve'        => 'Onayla',
    'btn_reject'         => 'Reddet',
    'btn_delete'         => 'Sil',
    'btn_edit'           => 'Düzenle',
    'btn_save'           => 'Kaydet',
    'btn_cancel'         => 'İptal',
    'btn_submit'         => 'Gönder',
    'btn_publish'        => 'Yayınla',
    'btn_new'            => 'Yeni ekle',
    'btn_back'           => 'Geri',
    'btn_ban'            => 'Yasakla',
    'btn_unban'          => 'Yasağı kaldır',
    'confirm_delete'     => 'Silmek istediğinize emin misiniz?',

    // --- Flash messages ---------------------------------------------------
    'flash_success'      => 'İşlem başarıyla tamamlandı.',
    'flash_error'        => 'Bir hata oluştu. Lütfen tekrar deneyin.',
    'flash_saved'        => 'Değişiklikler kaydedildi.',
    'flash_deleted'      => 'Kayıt silindi.',
    'flash_unauthorized' => 'Bu işlem için yetkiniz yok.',
    'flash_login_required' => 'Devam etmek için giriş yapmalısınız.',
    'flash_csrf'         => 'Oturum doğrulaması başarısız. Lütfen tekrar deneyin.',

    // --- Static pages -----------------------------------------------------
    'about_page_title'   => 'Hakkımızda',
    'contact_page_title' => 'İletişim',
    'contact_name'       => 'Adınız',
    'contact_email'      => 'E-posta adresiniz',
    'contact_subject'    => 'Konu',
    'contact_message'    => 'Mesajınız',
    'contact_send'       => 'Mesajı gönder',
    'contact_success'    => 'Mesajınız gönderildi. En kısa sürede size dönüş yapacağız.',
    'contact_error'      => 'Mesajınız gönderilemedi. Lütfen daha sonra tekrar deneyin.',

    // --- Footer -----------------------------------------------------------
    'footer_about'       => 'real.com.tr, teknoloji ve tarih başta olmak üzere birçok alanda güvenilir ve derinlemesine bilgi sunan bilgi platformudur.',
    'footer_links'       => 'Bağlantılar',
    'footer_follow'      => 'Bizi takip edin',
    'copyright'          => '© {year} real.com.tr — Tüm hakları saklıdır.',

    // --- Language switcher ------------------------------------------------
    'lang_tr'            => 'Türkçe',
    'lang_en'            => 'English',
    'lang_switch'        => 'Dil değiştir',

    // --- Errors -----------------------------------------------------------
    'error_404_title'    => 'Sayfa bulunamadı',
    'error_404_message'  => 'Aradığınız sayfa taşınmış veya hiç var olmamış olabilir.',
    'error_500_title'    => 'Bir şeyler ters gitti',
    'error_500_message'  => 'Beklenmeyen bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
    'error_back_home'    => 'Ana sayfaya dön',

    // --- Home / hero / sections (used by views) ---
    'hero_heading'        => 'Bilgiye açılan kapınız',
    'hero_subtitle'       => 'Teknolojiden tarihe, binlerce konuda güvenilir ve özgür ansiklopedi içeriği.',
    'search_prompt'       => 'Ne öğrenmek istersiniz?',
    'section_trending'    => 'Öne Çıkanlar',
    'section_latest'      => 'Son Eklenenler',
    'section_categories'  => 'Kategoriler',
    'subcategories'       => 'Alt kategoriler',
    'no_comments_yet'     => 'Henüz yorum yok. İlk yorumu siz yapın.',
    'btn_submit_comment'  => 'Yorum Gönder',
    'comment_moderation_note' => 'Yorumunuz onaylandıktan sonra yayınlanacaktır.',
    'pagination_label'    => 'Sayfalama',
    'footer_pages'        => 'Sayfalar',
    'footer_rights'       => 'Tüm hakları saklıdır.',

    // --- About page ---
    'about_lead'              => 'real.com.tr, herkes için özgür ve erişilebilir bilgi sunmayı amaçlayan çok dilli bir ansiklopedi platformudur.',
    'about_mission_title'     => 'Misyonumuz',
    'about_mission_body'      => 'Doğru, tarafsız ve kaynaklara dayalı bilgiyi Türkçe ve İngilizce olarak ücretsiz sunmak.',
    'about_editorial_title'   => 'Editöryel İlkeler',
    'about_editorial_body'    => 'Her içerik, yayınlanmadan önce editör ekibimiz tarafından gözden geçirilir ve onaylanır.',
    'about_contribute_title'  => 'Katkıda Bulunun',
    'about_contribute_body'   => 'Yazar olarak başvurabilir, onay sürecinin ardından kendi makalelerinizi yayınlayabilirsiniz.',

    // --- Contact page ---
    'contact_lead'          => 'Sorularınız, önerileriniz veya iş birlikleri için bize ulaşın.',
    'contact_field_name'    => 'Adınız',
    'contact_field_email'   => 'E-posta adresiniz',
    'contact_field_subject' => 'Konu',
    'contact_field_message' => 'Mesajınız',
    'contact_submit'        => 'Gönder',
    'contact_direct_label'  => 'Doğrudan e-posta',

    // --- Author editor / dashboard ---
    'editor_new_title'  => 'Yeni Yazı',
    'editor_edit_title' => 'Yazıyı Düzenle',
    'dashboard_title'   => 'Yazar Paneli',

    // --- Admin: chrome ---
    'admin_panel'         => 'Yönetim Paneli',
    'admin_view_site'     => 'Siteyi Görüntüle',
    'admin_logout'        => 'Çıkış Yap',
    'admin_nav_dashboard' => 'Kontrol Paneli',
    'admin_nav_articles'  => 'Yazılar',
    'admin_nav_categories' => 'Kategoriler',
    'admin_nav_users'     => 'Kullanıcılar',
    'admin_nav_comments'  => 'Yorumlar',
    'admin_nav_tags'      => 'Etiketler',
    'admin_nav_settings'  => 'Ayarlar',

    // --- Admin: dashboard stats ---
    'admin_stat_published'         => 'Yayında',
    'admin_stat_drafts'            => 'Taslaklar',
    'admin_stat_pending_articles'  => 'Bekleyen Yazılar',
    'admin_stat_pending_comments'  => 'Bekleyen Yorumlar',
    'admin_stat_users'             => 'Kullanıcılar',
    'admin_stat_views'             => 'Görüntülenme',
    'admin_recent_pending_articles' => 'Onay Bekleyen Son Yazılar',
    'admin_recent_pending_comments' => 'Onay Bekleyen Son Yorumlar',
    'admin_no_pending_articles'    => 'Onay bekleyen yazı yok.',
    'admin_no_pending_comments'    => 'Onay bekleyen yorum yok.',
    'admin_no_articles'            => 'Yazı bulunamadı.',
    'admin_no_categories'          => 'Kategori bulunamadı.',

    // --- Admin: table columns ---
    'admin_col_title'    => 'Başlık',
    'admin_col_author'   => 'Yazar',
    'admin_col_category' => 'Kategori',
    'admin_col_status'   => 'Durum',
    'admin_col_lang'     => 'Dil',
    'admin_col_views'    => 'Görüntülenme',
    'admin_col_actions'  => 'İşlemler',
    'admin_col_name'     => 'Ad',
    'admin_col_role'     => 'Rol',
    'admin_col_user'     => 'Kullanıcı',
    'admin_col_comment'  => 'Yorum',
    'admin_col_sort'     => 'Sıra',

    // --- Admin: actions ---
    'admin_action_edit'    => 'Düzenle',
    'admin_action_delete'  => 'Sil',
    'admin_action_publish' => 'Yayınla',
    'admin_action_approve' => 'Onayla',
    'admin_action_reject'  => 'Reddet',
    'admin_action_review'  => 'İncele',
    'admin_action_save'    => 'Kaydet',
    'admin_action_cancel'  => 'İptal',

    // --- Admin: forms ---
    'admin_article_edit'   => 'Yazıyı Düzenle',
    'admin_category_new'   => 'Yeni Kategori',
    'admin_category_edit'  => 'Kategoriyi Düzenle',
    'admin_field_title'    => 'Başlık',
    'admin_field_content'  => 'İçerik',
    'admin_field_excerpt'  => 'Özet',
    'admin_field_cover'    => 'Kapak Görseli',
    'admin_field_cover_hint' => 'JPG, PNG veya WebP. Otomatik olarak WebP formatına dönüştürülür.',
    'admin_field_meta_title'       => 'Meta Başlık',
    'admin_field_meta_description' => 'Meta Açıklama',
    'admin_field_name'        => 'Ad',
    'admin_field_description' => 'Açıklama',
    'admin_field_slug'        => 'URL Kısaltması (slug)',
    'admin_field_parent'      => 'Üst Kategori',
    'admin_field_no_parent'   => '— Ana kategori —',
    'admin_field_tags'        => 'Etiketler',
    'admin_field_tags_hint'   => 'Virgülle ayırın (örn: php, tarih, bilim).',
    'admin_slug_auto_hint'    => 'Boş bırakılırsa başlıktan otomatik oluşturulur.',
    'admin_seo_section'       => 'SEO Ayarları',
    'admin_confirm_delete'    => 'Silmek istediğinizden emin misiniz?',
    'admin_filter_all'        => 'Tümü',
    'admin_filter_apply'      => 'Filtrele',

    // --- Count labels (use a {count} placeholder) ---
    'label_article_count'  => '{count} makale',
    'search_result_count'  => '{count} sonuç bulundu',
];
