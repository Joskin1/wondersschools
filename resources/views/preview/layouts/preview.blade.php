<!doctype html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Apex Crown College | Admissions Prospectus')</title>
    <meta name="description" content="@yield('meta_description', 'Official admissions prospectus of Apex Crown College. A premier British-Nigerian secondary institution in Lagos.')" />

    <!-- Google Fonts: Fraunces & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,400;1,9..144,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (via local Vite or CDN with custom theme tokens) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              ink: '#0B2545',
              paper: '#FAF8F4',
              gold: '#C8A951',
              'gold-hover': '#b89840',
              hairline: '#E5E0D8',
            },
            fontFamily: {
              serif: ['Fraunces', 'Playfair Display', 'Georgia', 'serif'],
              sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
            }
          }
        }
      }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
      :root {
        --ink: #0B2545;
        --paper: #FAF8F4;
        --gold: #C8A951;
        --hairline: #E5E0D8;
      }
      body {
        background-color: var(--paper);
        color: #2D3748;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        font-size: 1.0625rem;
        line-height: 1.7;
      }
      h1, h2, h3, h4, .font-serif {
        font-family: 'Fraunces', 'Playfair Display', Georgia, serif;
      }
      .font-sans {
        font-family: 'Inter', sans-serif;
      }
      [x-cloak] { display: none !important; }
      
      /* Subtle single fade-up animation */
      @media (prefers-reduced-motion: no-preference) {
        .reveal-on-scroll {
          animation: fadeUp 0.4s ease-out forwards;
        }
      }
      @keyframes fadeUp {
        from {
          opacity: 0;
          transform: translateY(16px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
    </style>
  </head>

  <body class="bg-[#FAF8F4] text-[#2D3748] antialiased selection:bg-[#C8A951] selection:text-[#0B2545]">
    @yield('content')
  </body>
</html>
