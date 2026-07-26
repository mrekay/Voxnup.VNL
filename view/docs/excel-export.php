<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="text-center mb-15 mt-10">
                <h1 class="fs-2hx fw-bold mb-5 text-body-emphasis">Excel Export</h1>
                <div class="fs-5 text-muted fw-semibold w-lg-75 mx-auto">
                    VNL, Excel dosyası oluşturmak için <b>PhpSpreadsheet</b> kütüphanesini kullanır. İki farklı yaklaşım mevcuttur: SDTable'ın yerleşik export mekanizması ve doğrudan action içinde özel Excel oluşturma.
                </div>
            </div>

            <div class="row g-5 mb-10">
                <div class="col-md-6">
                    <div class="border border-2 border-primary rounded p-6 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge badge-circle badge-primary me-3 fs-6">A</span>
                            <h5 class="fw-bold mb-0">SDTable Entegreli Export</h5>
                        </div>
                        <p class="text-body-secondary fs-6 mb-0">DataTables üzerindeki export butonu otomatik devreye girer. <code>datatableToExcel.php</code> mevcut filtreleri koruyarak tüm veriyi çeker ve Excel dosyası üretir.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border border-2 border-success rounded p-6 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge badge-circle badge-success me-3 fs-6">B</span>
                            <h5 class="fw-bold mb-0">Manuel Excel Oluşturma</h5>
                        </div>
                        <p class="text-body-secondary fs-6 mb-0">Action sınıfı içinde PhpSpreadsheet API'sini doğrudan kullanarak özel formatlı, çok sayfalı veya şablonlu Excel dosyaları üretin.</p>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-10">
                <div class="card-body p-10">

                    <!--begin::Section 1-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">1. Genel Bakış</h3>
                        <p class="fs-5 text-body mb-4">
                            PhpSpreadsheet kütüphanesi <code>libs/excel/</code> dizininde bulunur. Kullanım için <code>use</code> ile namespace tanımlanır; sınıflar otomatik olarak dahil edilir.
                        </p>

                        <div class="d-flex align-items-center bg-body-tertiary border border-dashed rounded p-4 mb-4">
                            <i class="fa-solid fa-folder fs-1 text-warning me-3"></i>
                            <div>
                                <span class="fs-5 fw-bold text-body font-monospace d-block">libs/excel/</span>
                                <span class="text-muted fs-7">PhpSpreadsheet kütüphane dizini.</span>
                            </div>
                        </div>

                        <div class="bg-dark rounded p-5 mb-4">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Yeni spreadsheet oluşturma
$spreadsheet = new Spreadsheet();

// Şablondan okuma
$reader      = IOFactory::createReader('Xlsx');
$spreadsheet = $reader->load($sablonYolu);

// Yazıcı oluşturma
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 1-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 2-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">2. SDTable Entegreli Excel Export (<code>datatableToExcel.php</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            SDTable tarafından oluşturulan tablolara otomatik olarak Excel export butonu eklenir. Kullanıcı butona tıkladığında arka planda şu süreç işler:
                        </p>

                        <div class="border rounded p-5 bg-body-tertiary mb-6">
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">1</span>
                                <div>
                                    <span class="fw-bold text-body">DataTables JS, mevcut filtre değerlerini ve sütun bilgilerini toplar</span>
                                    <div class="text-muted fs-7 mt-1">Aktif filtreler ve kolon isimleri <code>datatableToExcel.php</code>'ye POST ile gönderilir.</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">2</span>
                                <div>
                                    <span class="fw-bold text-body">Action, <code>length=-1</code> ile yeniden çağrılır</span>
                                    <div class="text-muted fs-7 mt-1">Sayfalama devre dışı bırakılarak filtrelenmiş tüm kayıtlar tek seferde alınır.</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-5">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">3</span>
                                <div>
                                    <span class="fw-bold text-body"><code>files/template/GenelDataTable.xlsx</code> şablonu yüklenir</span>
                                    <div class="text-muted fs-7 mt-1">Şablon dosyasındaki header ve veri satırı stilleri kullanılarak Excel doldurulur.</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-0">
                                <span class="badge badge-circle badge-primary me-4 mt-1 flex-shrink-0">4</span>
                                <div>
                                    <span class="fw-bold text-body">Dosya <code>files/datatable/{kullanici_klasoru}/</code> dizinine kaydedilir</span>
                                    <div class="text-muted fs-7 mt-1">Oluşturulan dosyanın URL'si JSON yanıtıyla döndürülür; tarayıcı indirme işlemini başlatır.</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert bg-warning-subtle border border-warning d-flex flex-column flex-sm-row p-5 mb-0">
                            <i class="fa-solid fa-circle-info fs-2hx text-warning me-4 mb-5 mb-sm-0"></i>
                            <div class="d-flex flex-column pe-0 pe-sm-10">
                                <h5 class="mb-1">Gereksinim</h5>
                                <span><code>files/template/GenelDataTable.xlsx</code> şablon dosyası mevcut olmalıdır. Bu dosyanın <code>A1</code> hücresi başlık stili, <code>A2</code> hücresi veri satırı stilini içermelidir. Export sistemi bu stilleri otomatik olarak çoğaltır.</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 2-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 3-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">3. Manuel Excel Oluşturma (Action İçinde)</h3>
                        <p class="fs-5 text-body mb-4">
                            Özel rapor formatları, çok sayfalı çalışma kitapları veya özel şablonlar için action içinde PhpSpreadsheet API'sini doğrudan kullanabilirsiniz. Veri çekimi her zaman model sınıfı üzerinden yapılmalıdır.
                        </p>

                        <div class="bg-dark rounded p-5 mb-5">
                            <pre class="text-white fs-6 font-monospace mb-0" style="background: transparent; white-space: pre-wrap;"><code>&lt;?php
// actions/action_rapor.php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class action_rapor
{
    public function kullaniciRaporu($req): void
    {
        Auth::CheckLogin();

        // Şablonu yükle
        $sablonYolu = getDocumentRoot() . 'files/template/GenelDataTable.xlsx';
        $reader     = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($sablonYolu);
        $sheet       = $spreadsheet->getActiveSheet();

        // Başlık satırı stili — A1 hücresinden kopyalanır
        $baslikStil = $sheet->getStyle('A1');

        // Başlıkları yaz
        $basliklar = ['ID', 'Ad Soyad', 'E-posta', 'Kayıt Tarihi'];
        foreach ($basliklar as $i => $baslik) {
            $sutun = chr(65 + $i); // A, B, C, D
            $sheet->setCellValueExplicit($sutun . '1', $baslik, DataType::TYPE_STRING);
            $sheet->duplicateStyle($baslikStil, $sutun . '1');
            $sheet->getColumnDimension($sutun)->setAutoSize(true);
        }

        // Model üzerinden veri çek — zorunlu
        $kullanicilar = User::SelectList([], ['limit' => -1])->get();

        // Veri satırı stili — A2 hücresinden kopyalanır
        $veriStil = $sheet->getStyle('A2');

        $satirNo = 2;
        foreach ($kullanicilar as $kullanici) {
            $sheet->setCellValueExplicit('A' . $satirNo, $kullanici->Id,   DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('B' . $satirNo, $kullanici->Name . ' ' . $kullanici->Surname, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $satirNo, $kullanici->Email,      DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $satirNo, $kullanici->InsertDate, DataType::TYPE_STRING);

            $sheet->duplicateStyle($veriStil, 'A' . $satirNo . ':D' . $satirNo);
            $satirNo++;
        }

        // Kaydet
        $dosyaAdi  = 'kullanici_raporu_' . date('Y-m-d_His') . '.xlsx';
        $kayitYolu = getDocumentRoot() . 'files/datatable/' . Auth::User()->Id . '/';

        if (!is_dir($kayitYolu)) {
            mkdir($kayitYolu, 0755, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($kayitYolu . $dosyaAdi);

        $url = getSiteUrl() . 'files/datatable/' . Auth::User()->Id . '/' . $dosyaAdi;
        returnSuccessDataOnly(['url' => $url, 'dosya_adi' => $dosyaAdi]);
    }
}
</code></pre>
                        </div>
                    </div>
                    <!--end::Section 3-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 4-->
                    <div class="mb-10">
                        <h3 class="fw-bold text-body-emphasis mb-4">4. PhpSpreadsheet Temel Kullanım Referansı</h3>

                        <div class="table-responsive border rounded bg-body-tertiary mb-6">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-380px">Metot / Zincirleme</th>
                                    <th class="min-w-300px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><code>setCellValue('A1', 'Değer')</code></td>
                                    <td>Hücreye değer yazar. Tip otomatik algılanır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>setCellValueExplicit('A1', 'Değer', DataType::TYPE_STRING)</code></td>
                                    <td>Hücreye tip belirterek değer yazar. Büyük sayılar ve tarihler için tercih edilir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>getStyle('A1:D1')->getAlignment()->setWrapText(true)</code></td>
                                    <td>Hücre içeriğinin satır kaydırmasını (word wrap) etkinleştirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>getColumnDimension('A')->setAutoSize(true)</code></td>
                                    <td>Sütun genişliğini içeriğe göre otomatik ayarlar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>getRowDimension(1)->setRowHeight(30)</code></td>
                                    <td>Satır yüksekliğini piksel cinsinden ayarlar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>duplicateStyle($stil, 'A2:D2')</code></td>
                                    <td>Bir hücrenin stilini başka hücre aralığına kopyalar.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$spreadsheet->setActiveSheetIndex(0)</code></td>
                                    <td>Aktif çalışma sayfasını index numarasıyla seçer (0 = ilk sayfa).</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>$spreadsheet->createSheet()->setTitle('Sayfa 2')</code></td>
                                    <td>Yeni bir çalışma sayfası oluşturur ve adlandırır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>mergeCells('A1:D1')</code></td>
                                    <td>Belirtilen hücre aralığını birleştirir.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><code>getHighestRow()</code></td>
                                    <td>Sayfadaki en son dolu satır numarasını döndürür.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="fw-bold text-body-emphasis mb-3">Tip Sabitleri (<code>DataType</code>)</h5>
                        <div class="d-flex flex-wrap gap-3 mb-5">
                            <span class="badge badge-light-primary fs-7 p-3 font-monospace">DataType::TYPE_STRING</span>
                            <span class="badge badge-light-success fs-7 p-3 font-monospace">DataType::TYPE_NUMERIC</span>
                            <span class="badge badge-light-info fs-7 p-3 font-monospace">DataType::TYPE_BOOL</span>
                            <span class="badge badge-light-warning fs-7 p-3 font-monospace">DataType::TYPE_FORMULA</span>
                            <span class="badge badge-light-danger fs-7 p-3 font-monospace">DataType::TYPE_NULL</span>
                        </div>
                    </div>
                    <!--end::Section 4-->

                    <div class="separator separator-dashed my-8"></div>

                    <!--begin::Section 5-->
                    <div class="mb-0">
                        <h3 class="fw-bold text-body-emphasis mb-4">5. Excel Şablonu (<code>GenelDataTable.xlsx</code>)</h3>
                        <p class="fs-5 text-body mb-4">
                            SDTable export sistemi ve özel raporlar için ortak şablon dosyası kullanılır. Bu dosya hem başlık hem de veri satırı stillerini barındırır.
                        </p>

                        <div class="d-flex align-items-center bg-body-tertiary border border-dashed rounded p-4 mb-5">
                            <i class="bi bi-file-earmark-excel fs-1 text-success me-3"></i>
                            <div>
                                <span class="fs-5 fw-bold text-body font-monospace d-block">files/template/GenelDataTable.xlsx</span>
                                <span class="text-muted fs-7">SDTable export ve özel rapor şablonu</span>
                            </div>
                        </div>

                        <div class="table-responsive border rounded bg-body-tertiary mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom">
                                    <th class="ps-5 min-w-120px">Hücre</th>
                                    <th class="min-w-150px">Rol</th>
                                    <th class="min-w-350px">Açıklama</th>
                                </tr>
                                </thead>
                                <tbody class="text-body fw-semibold">
                                <tr>
                                    <td class="ps-5"><span class="badge badge-light-primary">A1</span></td>
                                    <td>Başlık Stili</td>
                                    <td>Bu hücrenin font, arka plan, kenarlık ve hizalama stilleri başlık satırına uygulanır. <code>duplicateStyle($baslikStil, 'A1:Z1')</code> ile çoğaltılır.</td>
                                </tr>
                                <tr>
                                    <td class="ps-5"><span class="badge badge-light-success">A2</span></td>
                                    <td>Veri Satırı Stili</td>
                                    <td>Bu hücrenin stilleri tüm veri satırlarına uygulanır. Her satır için <code>duplicateStyle($veriStil, 'A' . $n . ':Z' . $n)</code> çağrılır.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert bg-info-subtle border border-info d-flex flex-column flex-sm-row p-5 mb-0">
                            <i class="fa-solid fa-pencil fs-2hx text-info me-4 mb-5 mb-sm-0"></i>
                            <div class="d-flex flex-column pe-0 pe-sm-10">
                                <h5 class="mb-1">Şablonu Özelleştirme</h5>
                                <span><code>GenelDataTable.xlsx</code> dosyasını Excel'de açarak <code>A1</code> ve <code>A2</code> hücrelerinin stillerini istediğiniz gibi değiştirebilirsiniz. Tüm export işlemleri bu stilleri referans alır.</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section 5-->

                </div>
            </div>

        </div>
    </div>
</div>
