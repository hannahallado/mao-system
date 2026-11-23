<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MAO</title>
  <link rel="stylesheet" href="<?= asset('css/landing.css') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body<?= ($showSplash ?? true) ? '' : ' class="no-splash is-instant"' ?>>
  <?php if ($showSplash ?? true): ?>
  <div class="loading-screen">
    <div class="loading-content">
      <div class="loading-logo">
  <img src="<?= asset('img/logo.png') ?>" alt="MAO logo">
</div>
      <div class="loading-tagline">Municipal Agriculture Office</div>
    </div>
  </div>
  <?php endif; ?>

  <section class="hero" id="Hero">
    <img
      src="<?= asset('img/18-upscaled.jpg') ?>"
      alt="Farmland and fishing community"
      class="hero-bg"
    />
    <div class="hero-overlay"></div>

    <nav class="nav">
      <div class="logo">
        <a href="#Hero" class="logo-link">MAO</a>
      </div>
      <div class="nav-right">
        <button type="button" class="access-btn" onclick="toggleFaq()" aria-expanded="false" aria-controls="modal">
          <span>FAQs</span>
          <div class="access-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg></div>
        </button>
        <div class="menu-icon" id="menuButton" aria-label="menu button" role="button">
          <div class="menu-btn-wrapper">
          <span class="bar top"></span>
          <span class="bar bottom"></span>
          </div>
          <div class="menu-links">
      <span class="menu-label">Menu</span>
      <a href="#Concept">
        About
        <span class="inline-preview">
          <img src="<?= asset('img/9-upscaled.jpg') ?>" alt="About preview">
        </span>
      </a>
      <a href="#Experience">
        Experiences
        <span class="inline-preview">
          <img src="<?= asset('img/10-upscaled.jpg') ?>" alt="Services preview">
        </span>
      </a>
      <a href="#Services">
        Services
        <span class="inline-preview"> 
          <img src="<?= asset('img/11-upscaled.jpg') ?>" alt="VIP preview">
        </span>
      </a>
      <a href="#Contact">
        Contact
        <span class="inline-preview">
          <img src="<?= asset('img/13-upscaled.jpg') ?>" alt="Contact preview">
        </span>
      </a>
    </div>
        </div>
      </div>
    </nav>

    <div class="modal-overlay faq-modal" id="modal">
  <div class="faq-panel" role="region" aria-labelledby="faqTitle">
    <div class="faq-scroll" tabindex="0">
      <header class="faq-header">
        <h2 class="faq-title" id="faqTitle">Frequently Ask Questions</h2>
        <!-- Same X as the login modal (styles in .login-close-btn); part of the
             header, so it scrolls away with the content -->
        <button type="button" class="login-close-btn faq-close-btn" onclick="toggleFaq()" aria-label="Close FAQs">
          <span class="bar"></span>
          <span class="bar"></span>
        </button>
      </header>
        <section class="faq-section">
          <h3 class="faq-section-title">General</h3>
          <div class="faq-list">
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-1" aria-expanded="false" aria-controls="faq-a-1">
                <span>What is MAO</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-1">
              <div class="faq-answer-inner">
                <p>MAO is an Agriculture and Fisheries Information Management System. Office personnel use it to keep the farmer and fisherfolk registries, agricultural records, production data, and program information in one organized place, supported by GIS maps, dashboards, and reports.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-2" aria-expanded="false" aria-controls="faq-a-2">
                <span>Who can use MAO?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-2">
              <div class="faq-answer-inner">
                <p>MAO is operated by authorized MAO personnel. Farmers, fisherfolk, and residents take part by coordinating with the office, whose staff record, update, and review information on their behalf.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-3" aria-expanded="false" aria-controls="faq-a-3">
                <span>What information is available through the system?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-3">
              <div class="faq-answer-inner">
                <p>The system keeps records of registered farmers and fisherfolk, farm and fishing area details, crop, livestock, and fisheries production, and the programs and assistance provided by the office. This information is shown on GIS maps and summarized in dashboards and reports for planning. Personal records are handled by office personnel and released according to the office's procedures.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-4" aria-expanded="false" aria-controls="faq-a-4">
                <span>How does MAO help the community?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-4">
              <div class="faq-answer-inner">
                <p>Organized records, maps, and reports help the office see where support is needed, monitor production, and keep track of the assistance it provides. This allows programs to be planned and delivered more fairly and transparently.</p>
              </div>
            </div>
          </div>
          </div>
        </section>
        <section class="faq-section">
          <h3 class="faq-section-title">Farmers</h3>
          <div class="faq-list">
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-5" aria-expanded="false" aria-controls="faq-a-5">
                <span>How can I update my farmer registration?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-5">
              <div class="faq-answer-inner">
                <p>Visit the Municipal Agriculture Office and tell the staff what has changed, such as your contact details, farm location, farm area, or crops planted. Please bring a valid ID and any supporting documents the office may ask for. Staff will verify the details and update your record in the Farmer Registry.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-6" aria-expanded="false" aria-controls="faq-a-6">
                <span>What assistance programs are available?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-6">
              <div class="faq-answer-inner">
                <p>Available programs depend on what the Municipal Agriculture Office and its partner agencies are implementing, and may change by season and funding. Please check the office's announcements or inquire at the office to learn which programs are currently open. Assistance given to farmers is recorded by the office.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-7" aria-expanded="false" aria-controls="faq-a-7">
                <span>How can I participate in trainings?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-7">
              <div class="faq-answer-inner">
                <p>Trainings and orientations are announced by the office when they are scheduled. You may inform the office of your interest, and staff will advise you on the schedule, venue, and any requirements. Slots may be limited, so early coordination is encouraged.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-8" aria-expanded="false" aria-controls="faq-a-8">
                <span>How are farm records maintained?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-8">
              <div class="faq-answer-inner">
                <p>Office personnel encode farm information such as location, area, crops, and production into the system's Agricultural Records and Production Monitoring. Records are checked and updated based on the information farmers provide and on the office's own validation. Farm locations may also be shown on GIS maps.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-9" aria-expanded="false" aria-controls="faq-a-9">
                <span>Can I view my agricultural data online?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-9">
              <div class="faq-answer-inner">
                <p>MAO is used by office personnel, so records are not open for public viewing online. If you would like to see or confirm the information on your record, please visit the office with a valid ID. Staff can review it with you and correct it during the same visit.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-10" aria-expanded="false" aria-controls="faq-a-10">
                <span>Who should I contact for concerns?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-10">
              <div class="faq-answer-inner">
                <p>Please coordinate directly with the Municipal Agriculture Office at the Municipal Hall. Office staff can receive your questions, concerns, and requests and refer them to the proper personnel. Contact details are listed at the bottom of the main page.</p>
              </div>
            </div>
          </div>
          </div>
        </section>
        <section class="faq-section">
          <h3 class="faq-section-title">Fisherfolk</h3>
          <div class="faq-list">
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-11" aria-expanded="false" aria-controls="faq-a-11">
                <span>How can I update my fisherfolk registration?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-11">
              <div class="faq-answer-inner">
                <p>Visit the Municipal Agriculture Office and inform the staff of any changes, such as your contact details, fishing area, or boat and gear information. Please bring a valid ID and any supporting documents the office may ask for. Staff will verify the details and update your record in the Fisherfolk Registry.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-12" aria-expanded="false" aria-controls="faq-a-12">
                <span>What support programs are available?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-12">
              <div class="faq-answer-inner">
                <p>Support programs depend on what the Municipal Agriculture Office and its partner agencies are implementing, and may change depending on funding and priorities. Please follow the office's announcements or inquire at the office to learn which programs are currently open to fisherfolk.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-13" aria-expanded="false" aria-controls="faq-a-13">
                <span>How is fisheries data maintained?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-13">
              <div class="faq-answer-inner">
                <p>Office personnel encode fisheries information, such as registered fisherfolk, fishing activity, and production, into the system and update it regularly. Records are verified with the information provided by fisherfolk, and fishing areas may be shown on GIS maps for planning.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-14" aria-expanded="false" aria-controls="faq-a-14">
                <span>Can I request record corrections?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-14">
              <div class="faq-answer-inner">
                <p>Yes. If any detail in your record is incorrect or out of date, you may request a correction at the office. Please bring a valid ID and any document that shows the correct information. Staff will verify it and update your record.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-15" aria-expanded="false" aria-controls="faq-a-15">
                <span>How can I receive announcements?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-15">
              <div class="faq-answer-inner">
                <p>Announcements are shared through the office's regular channels, such as notices at the Municipal Hall and coordination with barangay officials and fisherfolk groups. Giving the office your current contact details helps ensure that you are informed of schedules and activities.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-16" aria-expanded="false" aria-controls="faq-a-16">
                <span>Where can I get further assistance?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-16">
              <div class="faq-answer-inner">
                <p>You may visit the Municipal Agriculture Office at the Municipal Hall, where staff can answer questions about registration, records, and available programs. Contact details are listed at the bottom of the main page.</p>
              </div>
            </div>
          </div>
          </div>
        </section>
        <section class="faq-section">
          <h3 class="faq-section-title">Programs &amp; Assistance</h3>
          <div class="faq-list">
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-17" aria-expanded="false" aria-controls="faq-a-17">
                <span>What agricultural assistance programs may be available?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-17">
              <div class="faq-answer-inner">
                <p>Programs may include support for farm inputs, equipment, training, and livelihood for farmers and fisherfolk, depending on available funding and the priorities of the municipality and partner agencies. The office announces programs that are currently open.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-18" aria-expanded="false" aria-controls="faq-a-18">
                <span>Who may qualify for assistance programs?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-18">
              <div class="faq-answer-inner">
                <p>Eligibility depends on each program. In general, applicants should be residents of the municipality and registered with the Farmer Registry or Fisherfolk Registry. Some programs have additional criteria, and the office will explain these when you inquire.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-19" aria-expanded="false" aria-controls="faq-a-19">
                <span>What requirements may be needed?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-19">
              <div class="faq-answer-inner">
                <p>Requirements vary by program, but may include a valid ID, proof of residence, proof of registration, and a completed application form. Some programs may ask for additional documents. Please confirm with the office before preparing your papers.</p>
              </div>
            </div>
          </div>
          <div class="faq-item">
            <h4 class="faq-heading">
              <button type="button" class="faq-question" id="faq-q-20" aria-expanded="false" aria-controls="faq-a-20">
                <span>How are program announcements communicated?</span>
                <span class="faq-icon" aria-hidden="true"></span>
              </button>
            </h4>
            <div class="faq-answer" id="faq-a-20">
              <div class="faq-answer-inner">
                <p>The office communicates announcements through notices at the Municipal Hall, coordination with barangay officials and farmer and fisherfolk groups, and direct inquiries at the office. Please keep your contact details updated with the office.</p>
              </div>
            </div>
          </div>
          </div>
        </section>
      <div class="faq-footer">
        <button type="button" class="faq-close" aria-label="Back to top"
  onclick="document.querySelector('.faq-scroll').scrollTo({ top: 0, behavior: 'smooth' })">
  ↑ Back to Top
</button>
      </div>
    </div>
  </div>
</div>

    <div class="hero-content">
      <div class="hero-top">
        <p class="hero-subtitle">
          Municipal Agriculture Office
        </p>
      </div>
      <div class="hero-main">
        <h1 class="hero-title">
          Agriculture and <br />
          Fisheries <br />
          Information System
        </h1>
      </div>
    </div>

    <div class="bottom-card">
      <img
        src="<?= asset('img/1-upscaled.jpg') ?>"
        alt="Farm and community"
        class="card-image"/>
      
      <div class="card-content">
        <h4>Visit MAO for your records.</h4>
      </div>
      <div class="card-btn"><a>
            <i class="fa-solid fa-location-arrow"></i>
          </a></div>
    </div>

    <div class="marquee">
      <p class="marquee-text">
        Secures agricultural information. 
        <br /> Transparent records.
        <br /> For our farmers and fisherfolk.
      </p>
    </div>
     
    <div class="login">
    <a href="#" class="login-btn" onclick="openLoginModal()">
      <i class="fa-solid fa-user"></i>
      <span class="login-text">Personnel</span>
    </a>
  </div>

  <!-- Green curtain shown while logging in; the dashboard fades in from the same color -->
  <div class="login-curtain" aria-hidden="true"></div>

  <div class="modal-overlay<?= ($showSplash ?? true) ? '' : ' is-autoopen' ?>" id="loginModal">
    <div class="modal-box">
      <button type="button" class="login-close-btn" onclick="closeLoginModal()" aria-label="Close login">
        <span class="bar"></span>
        <span class="bar"></span>
      </button>
      <form id="sweeshForm" method="POST" action="<?= url('/login') ?>">
        <?= Csrf::field() ?>
        <div class="login-form-fields">
          <div class="login-heading">
            <img src="<?= asset('img/logo.png') ?>" alt="MAO logo" class="login-logo" width="56" height="56">
            <h2 class="modal-title">Personnel Login</h2>
            <p class="login-subtitle">MAO Information Management System</p>
          </div>

          <label for="adminUsername">Username</label>
          <input type="text" id="adminUsername" name="username" autocomplete="username" placeholder="Enter username" required />

          <label for="adminPassword">Password</label>
          <input type="password" id="adminPassword" name="password" autocomplete="current-password" placeholder="Enter password" required />

          <div class="checkbox-row">
            <input type="checkbox" id="agree" required>
            <label for="agree">
              I agree to the <span class="terms-link" id="openTermsBtn" role="button" tabindex="0">Terms &amp; Conditions</span> of MAO Admin
            </label>
          </div>
        </div>

        <div class="login-terms-box" tabindex="0" aria-label="MAO Admin Terms and Conditions">
          <h3>MAO Information System Terms &amp; Conditions</h3>
          <p class="login-terms-intro">
            By accessing the Municipal Agriculture Office (MAO) Information System, you confirm that you are an authorized personnel (Municipal Agriculturist, Agricultural Extension Worker / Technician, Fisheries Personnel, or System Administrator) and agree to the following terms:
          </p>

          <h4>1. Authorized Access &amp; Account Responsibility</h4>
          <p>
            Access is strictly limited to official MAO duties. Technicians may encode and manage records for their assigned sectors or commodities, while the Municipal Agriculturist and authorized administrators oversee consolidated municipal data. You are responsible for keeping your credentials confidential and logging out after each session.
          </p>

          <h4>2. Accurate Encoding &amp; Record Validation</h4>
          <p>
            Personnel must verify supporting documents (such as valid IDs, RSBSA/FishR details, acknowledgment receipts, and field validation data) before updating Farmer and Fisherfolk Profiles (D1), Agricultural Resource Records (D2), GIS Location Data (D3), Activity &amp; Production Monitoring Records (D4), and Programs &amp; Assistance Records (D5).
          </p>

          <h4>3. Duplicate Prevention &amp; Assistance Integrity</h4>
          <p>
            Before endorsing or recording beneficiaries for seeds, fertilizers, farm machinery, livestock, boats, fishing gear, cash assistance, insurance, or livelihood interventions, personnel must check beneficiary history to prevent duplicate or inconsistent records.
          </p>

          <h4>4. Data Privacy &amp; Confidentiality</h4>
          <p>
            Personal, farm, fishery, and assistance records belong to the Municipal Agriculture Office and Local Government Unit. Information may not be copied, exported, shared, or disclosed to unauthorized individuals outside official LGU/DA reporting and public service procedures.
          </p>

          <h4>5. Reports, System Security &amp; Audit</h4>
          <p>
            Generated masterlists, production summaries, damage/pest reports, and accomplishment reports must only be used for official planning and agency submission. All record additions, edits, and deletions are subject to administrative review and regular data backup protocols.
          </p>
        </div>

        <button type="submit" class="modal-btn" id="loginSubmitBtn">Log in</button>
      </form>
    </div>
  </div>
  </section>

  <section id="Concept" class="immersive">
  <div class="immersive-bg"></div>
  <div class="immersive-inner">
    <div class="immersive-text">
      <h2>
        We build every record to be accurate and easy to access, from farmer registration to program monitoring.
        <br><br> AFIMS is designed for a community that expects reliable service and transparent public information.
      </h2>
    </div>
    <div class="immersive-image"></div>
  </div>
  <div class="immersive-extension">
        <div class="portrait"></div>
        <div class="portrait-text">
        <p>
          Farmers,<br>Fisherfolk,<br>Residents
        </p>
        </div>
        <div class="landscape"></div>
        <div class="landscape-text">
        <p>
          Accurate<br>Farm<br>Mapping
        </p>
        </div>
  </div>
  <div class="immersive-full-view">
    <h2 class="full-view-text">
    Focus on your harvest<br>Leave the records to us.
    </h2>
  </div>
  </section>

  <section class="long-beige-section">
  <div id="Experience" class="beige-content-100vh">
  <div class="top-text">
    Brings agriculture and fisheries services closer to you.
  </div>
    <div class="exp-carousel" id="expCarousel" role="region" aria-roledescription="carousel" aria-label="Agriculture and fisheries showcase" tabindex="0">
    <figure class="exp-card"><img src="<?= asset('img/7-upscaled.jpg') ?>" alt="Showcase image 1" draggable="false" loading="lazy" decoding="async"></figure>
    <figure class="exp-card"><img src="<?= asset('img/14-upscaled.jpg') ?>" alt="Showcase image 2" draggable="false" loading="lazy" decoding="async"></figure>
    <figure class="exp-card"><img src="<?= asset('img/30-upscaled.jpg') ?>" alt="Showcase image 3" draggable="false" loading="lazy" decoding="async"></figure>
    <figure class="exp-card"><img src="<?= asset('img/3-upscaled.jpg') ?>" alt="Showcase image 4" draggable="false" loading="lazy" decoding="async"></figure>
    <figure class="exp-card"><img src="<?= asset('img/11-upscaled.jpg') ?>" alt="Showcase image 5" draggable="false" loading="lazy" decoding="async"></figure>
    <figure class="exp-card"><img src="<?= asset('img/5-upscaled.jpg') ?>" alt="Showcase image 6" draggable="false" loading="lazy" decoding="async"></figure>
    <figure class="exp-card"><img src="<?= asset('img/13-upscaled.jpg') ?>" alt="Showcase image 7" draggable="false" loading="lazy" decoding="async"></figure>
  </div>
</div>

<div class="mao-showcase" id="maoShowcase" aria-label="MAO Showcase">
    <div class="mao-showcase-text">M A O</div>
  </div>
  <div class="services-top-bg" id="servicesTopBg" aria-hidden="true"></div>
    <div id="Services" class="interactive-block-wrapper">
  <div class="ib-header">
    <h2 class="ib-title">
      Much more than a records system
    </h2>
  </div>

  <div class="ib-list-container">

    <div class="ib-row" data-img="<?= asset('img/6-upscaled.jpg') ?>">
      <span class="ib-left">One registry for every<br> farmer and fisherfolk</span>
      <span class="ib-right">Every farmer and fisherfolk in the municipality is recorded in one organized registry, so no one is left behind.</span>
    </div>
    
    <div class="ib-row" data-img="<?= asset('img/7-upscaled.jpg') ?>">
      <span class="ib-left">Complete agricultural records</span>
      <span class="ib-right">Crop, livestock, and fishery data kept organized, with production monitored season after season.</span>
    </div>

    <div class="ib-row" data-img="<?= asset('img/12-upscaled.jpg') ?>">
      <span class="ib-left">Programs and assistance,<br> tracked with care</span>
      <span class="ib-right">From application to release, follow subsidies, seeds, and support programs with clear, transparent updates.</span>
    </div>

    <div class="ib-row" data-img="<?= asset('img/8-upscaled.jpg') ?>">
      <span class="ib-left">Farms and fishing areas,<br> mapped for planning</span>
      <span class="ib-right">GIS maps show where support is needed most, helping the office plan and respond with confidence.</span>
    </div>

    <div class="ib-row" data-img="<?= asset('img/15-upscaled.jpg') ?>">
      <span class="ib-left">Insights ready<br>when you need them</span>
      <span class="ib-right">Dashboard analytics and generated reports give the office and our partners clear data for better decisions.</span>
    </div>

  </div>
  
  <div class="ib-cursor-follower" id="cursorFollower">
    <img src="" alt="Preview" id="followerImg">
  </div>

  <div class="split-section part-1">
    <div class="split-image-box"><img src="<?= asset('img/6-upscaled.jpg') ?>" alt="" loading="lazy" decoding="async"></div>
    <div class="split-content-box">
      <h2 class="split-title">
        A community growing together
      </h2>
      <p class="split-desc">
        Whether you're a farmer, a fisherfolk, or a resident, a shared information system is a chance to work together. An open public service accessible to all generations.
      </p>
      
      <div class="split-features">
        <div class="feature-item">
          <i data-lucide="home" class="feature-icon"></i>
          <span>Farming families</span>
        </div>
        <div class="feature-item">
          <i data-lucide="fish" class="feature-icon"></i> <span>Fishing communities</span>
        </div>
        <div class="feature-item">
          <i data-lucide="party-popper" class="feature-icon"></i>
          <span>Local programs & events</span>
        </div>
      </div>
    </div>
  </div>

  <div class="split-section part-2">
    <div class="split-content-box">
      <h2 class="split-title">
        Accuracy in every detail
      </h2>
      <p class="split-desc">
        From farm plots to fishing grounds, we make sure every record is complete, current, and easy to find. MAO focuses on a system that feels welcoming and helpful for everyone who uses it, so agricultural information is always dependable.
      </p>
      
      <div class="split-features">
        <div class="feature-item">
          <i data-lucide="sparkles" class="feature-icon"></i>
          <span>Accurate Records</span>
        </div>
        <div class="feature-item">
          <i data-lucide="leaf" class="feature-icon"></i>
          <span>Local Insights</span>
        </div>
        <div class="feature-item">
          <i data-lucide="shield-check" class="feature-icon"></i>
          <span>Secure Information</span>
        </div>
      </div>
    </div>
    <div class="split-image-box"><img src="<?= asset('img/23-upscaled.jpg') ?>" alt="" loading="lazy" decoding="async"></div>
    </div>
  </div>

</div>
  </section>

<footer class="footer-cta-wrapper" id="Contact">
  <div class="footer-faq-prompt">
    <p class="footer-faq-text">
      Do you have any questions?<br />
      Maybe you can find the answer here!
    </p>
    <button type="button" class="footer-faq-btn" onclick="toggleFaq()">REVEAL FAQS</button>
  </div>

  <div class="curved-footer">
    <div class="curved-footer-logo">MAO</div>

    <div class="curved-footer-brands">
      <img src="<?= asset('img/CCS.png') ?>" alt="Partner 1" loading="lazy" />
      <img src="<?= asset('img/DOA.png') ?>" alt="Partner 2" loading="lazy" />
      <img src="<?= asset('img/PCAF.png') ?>" alt="Partner 3" loading="lazy" />
      <img src="<?= asset('img/PLOGO.png') ?>" alt="Partner 4" loading="lazy" />
    </div>

    <div class="curved-footer-socials">
      <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
      <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
      <a href="#" aria-label="Snapchat"><i class="fa-brands fa-snapchat"></i></a>
    </div>

    <div class="curved-footer-links">
      <a href="#Services">User Support</a>
      <a href="mailto:contact@mao.gov.ph">Email</a>
    </div>

    <p class="curved-footer-copy">
      MAO &copy; All Rights Reserved. Municipal Agriculture Office &copy; 2026
    </p>
  </div>
</footer>

<section class="hero hero-loop-clone" id="heroLoopClone" aria-hidden="true">
  <img
    src="<?= asset('img/18-upscaled.jpg') ?>"
    alt=""
    class="hero-bg"
  />
  <div class="hero-overlay"></div>

  <div class="hero-content">
    <div class="hero-top">
      <p class="hero-subtitle">
        Municipal Agriculture Office
      </p>
    </div>
    <div class="hero-main">
      <h1 class="hero-title">
        Agriculture and <br />
        Fisheries <br />
        Information System
      </h1>
    </div>
  </div>

  <div class="bottom-card">
    <img
      src="<?= asset('img/1-upscaled.jpg') ?>"
      alt=""
      class="card-image"
    />
    <div class="card-content">
      <h4>Visit MAO for your records.</h4>
    </div>
    <div class="card-btn">
      <i class="fa-solid fa-location-arrow"></i>
    </div>
  </div>

  <div class="marquee">
    <p class="marquee-text">
      Secures agricultural information.
      <br /> Transparent records.
      <br /> For our farmers and fisherfolk.
    </p>
  </div>
</section>

<script src="<?= asset('js/landing.js') ?>"></script>
</body>
</html> 