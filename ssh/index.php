<?php
/**
 * Advanced SSH Public Key Submission Portal
 * Features: Multi-format validation, Copy-to-clipboard, Success Animations, SweetAlert2 integration.
 */

$message = "";
$status_type = ""; 
$show_success_overlay = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = isset($_POST["name"]) ? htmlspecialchars(trim($_POST["name"])) : "";
    $key = isset($_POST["pubkey"]) ? trim($_POST["pubkey"]) : "";

    if (strpos($key, "PRIVATE KEY") !== false) {
        $message = "Security Alert: You've pasted a PRIVATE key. This is dangerous and should never be shared.";
        $status_type = "error";
    } 
    elseif (!preg_match('/^(ssh-ed25519|ssh-rsa|ecdsa-sha2-nistp256|ecdsa-sha2-nistp384|ecdsa-sha2-nistp521) /', $key)) {
        $message = "Invalid Format: SSH public keys must start with a valid algorithm (e.g., ssh-ed25519 or ssh-rsa).";
        $status_type = "error";
    } 
    else {
        $timestamp = date("Y-m-d H:i:s");
        $entry = "\n# --- Submission: $timestamp ---\n# Name: $name\n$key\n";
        $file = "ssh_authorized_keys.txt"; 

        if (file_put_contents($file, $entry, FILE_APPEND | LOCK_EX)) {
            $show_success_overlay = true;
            $status_type = "success";
            $message = "Access Provisioned: Your key for $name has been queued successfully.";
        } else {
            $message = "System Error: Failed to save the key. Check server file permissions.";
            $status_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSH Access Portal | PaperTrade VPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            overflow-x: hidden;
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            color: #e2e8f0;
        }

        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
        }

        .code-block {
            background: rgba(2, 6, 23, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
        }

        /* SweetAlert Glass Customization */
        .swal2-popup.swal-glass {
            background: rgba(30, 41, 59, 0.9) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            border-radius: 24px !important;
        }

        .input-focus-effect:focus {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .float-anim { animation: float 6s ease-in-out infinite; }
    </style>
    <script>
        function setTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('bg-blue-600/20', 'text-blue-400', 'border-blue-500/50'));
            
            document.getElementById('content-' + tabId).classList.remove('hidden');
            document.getElementById('btn-' + tabId).classList.add('bg-blue-600/20', 'text-blue-400', 'border-blue-500/50');
        }

        function copyToClipboard(elementId, btn) {
            const text = document.getElementById(elementId).innerText;
            const textarea = document.createElement("textarea");
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="text-emerald-400 text-[10px] font-bold">COPIED!</span>';
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Command Copied',
                showConfirmButton: false,
                timer: 1500,
                background: 'rgba(15, 23, 42, 0.9)',
                color: '#fff'
            });

            setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
        }

        function validateForm() {
            const key = document.getElementById('pubkey').value;
            if (key.includes("PRIVATE KEY")) {
                Swal.fire({
                    icon: 'error',
                    title: 'Security Breach Detected',
                    text: 'You attempted to paste a PRIVATE key. For your safety, this action has been blocked. Please use your .pub file.',
                    customClass: { popup: 'swal-glass' },
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }
            return true;
        }

        // Trigger SweetAlert on PHP response
        window.onload = () => {
            <?php if ($message): ?>
                Swal.fire({
                    icon: '<?php echo $status_type; ?>',
                    title: '<?php echo $status_type === "success" ? "Success!" : "Wait a moment..."; ?>',
                    text: '<?php echo $message; ?>',
                    customClass: { popup: 'swal-glass' },
                    confirmButtonColor: '#3b82f6',
                    timer: <?php echo $status_type === "success" ? "5000" : "10000"; ?>,
                    timerProgressBar: true
                });
            <?php endif; ?>
        };
    </script>
</head>
<body class="min-h-screen p-4 md:p-8 selection:bg-blue-500/30">

    <div class="max-w-5xl mx-auto animate__animated animate__fadeIn">
        <!-- Logo Section -->
        <div class="flex justify-center mb-8">
            <img src="../img/logo-horizontal.png" alt="PaperTrade Logo" class="h-12 md:h-16 float-anim" onerror="this.style.display='none'">
        </div>

        <!-- Header -->
        <header class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-white tracking-tight drop-shadow-md">VPS Access Provisioning</h1>
            <p class="mt-3 text-slate-400 text-lg">Securely submit your SSH public key to the <span class="text-blue-400 font-semibold">PaperTrade</span> cluster.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left: Form -->
            <div class="lg:col-span-7">
                <div class="glass-panel p-6 md:p-8 rounded-3xl shadow-2xl transition-all hover:border-white/20">
                    <h2 class="flex items-center gap-3 text-xl font-semibold text-white mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Submit Public Key
                    </h2>

                    <form method="POST" action="" onsubmit="return validateForm()" autocomplete="off" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-2">Identifier / User Name</label>
                            <input 
                                type="text" 
                                name="name" 
                                autocomplete="off"
                                placeholder="e.g. Satoshi Nakamoto"
                                class="w-full bg-slate-900/40 border border-white/10 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all placeholder:text-slate-600 text-white input-focus-effect"
                                required
                            >
                        </div>

                        <div>
                            <div class="flex justify-between items-end mb-2">
                                <label class="block text-sm font-medium text-slate-400">Public Key String</label>
                                <span class="text-[10px] uppercase tracking-widest text-blue-400 font-bold">Secure Input</span>
                            </div>
                            <textarea 
                                id="pubkey"
                                name="pubkey" 
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5..."
                                class="w-full bg-slate-900/40 border border-white/10 rounded-xl px-4 py-3.5 h-44 font-mono text-xs focus:ring-2 focus:ring-blue-500 outline-none transition-all placeholder:text-slate-600 resize-none text-blue-300 input-focus-effect"
                                required
                            ></textarea>
                        </div>

                        <button 
                            type="submit" 
                            class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-blue-900/40 transition-all transform hover:-translate-y-1 active:scale-[0.98] flex items-center justify-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Request Provisioning
                        </button>
                    </form>
                </div>

                <!-- Warning Section -->
                <div class="mt-6 p-5 glass-panel border-amber-500/20 rounded-2xl flex gap-4 animate__animated animate__fadeInUp">
                    <div class="shrink-0 text-amber-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="text-xs text-slate-400">
                        <p class="font-bold text-amber-400 uppercase tracking-widest mb-1">Security Guardrail</p>
                        A public key usually starts with <code class="text-slate-200">ssh-ed25519</code> or <code class="text-slate-200">ssh-rsa</code>. Never share files beginning with <code class="text-red-400">-----BEGIN OPENSSH PRIVATE KEY-----</code>.
                    </div>
                </div>
            </div>

            <!-- Right: Guides -->
            <div class="lg:col-span-5">
                <div class="glass-panel p-6 rounded-3xl h-full animate__animated animate__fadeInRight">
                    <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Generation Assistant
                    </h3>

                    <!-- Tabs -->
                    <div class="flex bg-black/40 p-1 rounded-xl mb-6 border border-white/5">
                        <button onclick="setTab('unix')" id="btn-unix" class="tab-btn flex-1 py-2 text-xs font-medium rounded-lg bg-blue-600/20 text-blue-400 border border-blue-500/50 transition-all">Unix/Mac</button>
                        <button onclick="setTab('winps')" id="btn-winps" class="tab-btn flex-1 py-2 text-xs font-medium rounded-lg text-slate-500 border border-transparent transition-all">Windows</button>
                        <button onclick="setTab('wingui')" id="btn-wingui" class="tab-btn flex-1 py-2 text-xs font-medium rounded-lg text-slate-500 border border-transparent transition-all">GUI Tool</button>
                    </div>

                    <!-- Unix Content -->
                    <div id="content-unix" class="tab-content space-y-6 animate__animated animate__fadeIn">
                        <section>
                            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">1. Generate ed25519 Pair</h4>
                            <div class="code-block p-4 rounded-xl group transition-all hover:border-blue-500/30">
                                <span id="cmd-u1" class="font-mono text-xs text-blue-300">ssh-keygen -t ed25519 -C "vps-access"</span>
                                <button onclick="copyToClipboard('cmd-u1', this)" class="absolute right-3 top-3 opacity-0 group-hover:opacity-100 p-2 bg-slate-800 rounded-lg text-slate-400 hover:text-white transition-all shadow-xl" title="Copy to Clipboard">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                                </button>
                            </div>
                        </section>
                        <section>
                            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">2. Output Public Key</h4>
                            <div class="code-block p-4 rounded-xl group transition-all hover:border-blue-500/30">
                                <span id="cmd-u2" class="font-mono text-xs text-blue-300">cat ~/.ssh/id_ed25519.pub</span>
                                <button onclick="copyToClipboard('cmd-u2', this)" class="absolute right-3 top-3 opacity-0 group-hover:opacity-100 p-2 bg-slate-800 rounded-lg text-slate-400 hover:text-white transition-all shadow-xl">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                                </button>
                            </div>
                        </section>
                    </div>

                    <!-- Win PS Content -->
                    <div id="content-winps" class="tab-content hidden space-y-6 animate__animated animate__fadeIn">
                        <section>
                            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">PowerShell Generation</h4>
                            <div class="code-block p-4 rounded-xl group transition-all hover:border-blue-500/30">
                                <span id="cmd-w1" class="font-mono text-xs text-blue-300">ssh-keygen.exe -t ed25519</span>
                                <button onclick="copyToClipboard('cmd-w1', this)" class="absolute right-3 top-3 opacity-0 group-hover:opacity-100 p-2 bg-slate-800 rounded-lg text-slate-400 hover:text-white transition-all shadow-xl">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                                </button>
                            </div>
                        </section>
                        <section>
                            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Retrieve Content</h4>
                            <div class="code-block p-4 rounded-xl group transition-all hover:border-blue-500/30">
                                <span id="cmd-w2" class="font-mono text-xs text-blue-300">Get-Content $env:USERPROFILE\.ssh\id_ed25519.pub</span>
                                <button onclick="copyToClipboard('cmd-w2', this)" class="absolute right-3 top-3 opacity-0 group-hover:opacity-100 p-2 bg-slate-800 rounded-lg text-slate-400 hover:text-white transition-all shadow-xl">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                                </button>
                            </div>
                        </section>
                    </div>

                    <!-- Win GUI Content -->
                    <div id="content-wingui" class="tab-content hidden space-y-4 animate__animated animate__fadeIn">
                        <p class="text-sm text-slate-400">Using <strong>PuTTYgen</strong>:</p>
                        <div class="p-4 bg-black/30 rounded-xl border border-white/5 space-y-3">
                            <ul class="text-xs text-slate-400 space-y-2 list-disc pl-4">
                                <li>Launch <span class="text-blue-400">PuTTYgen.exe</span></li>
                                <li>Select <span class="text-white">Ed25519</span> key type</li>
                                <li>Click <strong>Generate</strong> and move mouse randomly</li>
                                <li>Copy the string from "Public key for pasting..."</li>
                                <li><strong>Save Private Key</strong> (.ppk) safely!</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>