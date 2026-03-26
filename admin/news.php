<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_admin();

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'create';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $publishedAtRaw = trim($_POST['published_at'] ?? '');
    $publishedAt = $publishedAtRaw === '' ? date('Y-m-d H:i:s') : str_replace('T', ' ', $publishedAtRaw) . ':00';

    try {
        if ($mode === 'delete' && $id !== false && $id !== null) {
            $deleteStmt = $pdo->prepare('DELETE FROM news WHERE id = :id');
            $deleteStmt->execute(['id' => $id]);
            set_flash('success', 'News deleted.');
        } elseif ($title !== '' && $content !== '') {
            if ($mode === 'update' && $id !== false && $id !== null) {
                $updateStmt = $pdo->prepare(
                    'UPDATE news
                     SET title = :title, content = :content, is_published = :is_published, published_at = :published_at
                     WHERE id = :id'
                );
                $updateStmt->execute([
                    'title' => $title,
                    'content' => $content,
                    'is_published' => $isPublished,
                    'published_at' => $isPublished === 1 ? $publishedAt : null,
                    'id' => $id,
                ]);
                set_flash('success', 'News updated.');
            } else {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO news (title, content, is_published, published_at, created_by, created_at)
                     VALUES (:title, :content, :is_published, :published_at, :created_by, NOW())'
                );
                $insertStmt->execute([
                    'title' => $title,
                    'content' => $content,
                    'is_published' => $isPublished,
                    'published_at' => $isPublished === 1 ? $publishedAt : null,
                    'created_by' => $user['id'],
                ]);
                set_flash('success', 'News created.');
            }
        } else {
            set_flash('error', 'Title and content are required.');
        }
    } catch (PDOException $e) {
        set_flash('error', 'Could not save news item.');
    }

    header('Location: ' . app_url('/admin/news.php'));
    exit;
}

$newsRows = $pdo->query('SELECT id, title, content, is_published, published_at, created_at FROM news ORDER BY id DESC')->fetchAll();

$pageTitle = 'Admin News';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Admin: Manage News</h1>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 id="newsFormTitle" class="h5 mb-0">Create News</h2>
            <button id="cancelEditBtn" type="button" class="btn btn-sm btn-outline-secondary d-none">Cancel Edit</button>
        </div>
        <form id="newsCreateForm" method="post" class="row g-3">
            <input id="newsFormMode" type="hidden" name="mode" value="create">
            <input id="newsFormId" type="hidden" name="id" value="">
            <div class="col-12">
                <label for="title" class="form-label">Title</label>
                <input id="title" name="title" class="form-control" required>
            </div>
            <div class="col-12">
                <label for="content" class="form-label">Content</label>
                <textarea id="content" name="content" class="form-control" rows="4" required></textarea>
            </div>
            <div class="col-md-4">
                <label for="published_at" class="form-label">Publish Time</label>
                <input id="published_at" name="published_at" type="datetime-local" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="is_published" name="is_published" checked>
                    <label class="form-check-label" for="is_published">Published</label>
                </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button id="newsSubmitBtn" class="btn btn-primary w-100" type="submit">Create News</button>
            </div>
        </form>
    </div>
</div>

<?php if ($newsRows === []): ?>
    <div class="alert alert-info">No news created yet.</div>
<?php else: ?>
    <h2 class="h5 mb-3">News List</h2>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="newsTable" class="table table-striped table-hover align-middle mb-0 js-paginated-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Published At</th>
                        <th>Created At</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($newsRows as $row): ?>
                        <?php
                        $newsId = (int) $row['id'];
                        $viewModalId = 'viewNewsModal' . $newsId;
                        $publishedLocal = $row['published_at'] === null
                            ? ''
                            : str_replace(' ', 'T', substr((string) $row['published_at'], 0, 16));
                        $editPayload = base64_encode((string) json_encode([
                            'id' => $newsId,
                            'title' => $row['title'],
                            'content' => $row['content'],
                            'is_published' => (int) $row['is_published'],
                            'published_at' => $publishedLocal,
                        ], JSON_UNESCAPED_UNICODE));
                        ?>
                        <tr>
                            <td><?= e((string) $newsId) ?></td>
                            <td><?= e($row['title']) ?></td>
                            <td>
                                <?php if ((int) $row['is_published'] === 1): ?>
                                    <span class="badge bg-success-subtle text-success-emphasis">Published</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(format_datetime($row['published_at'] ?? null)) ?></td>
                            <td><?= e(format_datetime($row['created_at'] ?? null)) ?></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#<?= e($viewModalId) ?>" title="View" aria-label="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5"/></svg>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-primary js-edit-news" data-news-payload="<?= e($editPayload) ?>" title="Edit" aria-label="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M15.502 1.94a.5.5 0 0 1 0 .706l-1.793 1.793-2.147-2.147 1.793-1.793a.5.5 0 0 1 .707 0l1.44 1.44z"/><path d="M11.061 3.146 3.5 10.707V13.5h2.793l7.561-7.561z"/><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a.5.5 0 0 0 0-1h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 0-1 0z"/></svg>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteNewsModal" data-news-id="<?= e((string) $newsId) ?>" data-news-title="<?= e($row['title']) ?>" title="Delete" aria-label="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1 0-2H5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1h2.5a1 1 0 0 1 1 1M6 2v1h4V2z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php foreach ($newsRows as $row): ?>
        <?php
        $newsId = (int) $row['id'];
        $viewModalId = 'viewNewsModal' . $newsId;
        ?>
        <div class="modal fade" id="<?= e($viewModalId) ?>" tabindex="-1" aria-labelledby="<?= e($viewModalId) ?>Label" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="<?= e($viewModalId) ?>Label">View News #<?= e((string) $newsId) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6 class="mb-2"><?= e($row['title']) ?></h6>
                        <p class="text-muted mb-2">Status: <?= (int) $row['is_published'] === 1 ? 'Published' : 'Draft' ?></p>
                        <p class="text-muted mb-3">Published At: <?= e(format_datetime($row['published_at'] ?? null)) ?></p>
                        <div class="border rounded p-3 bg-light-subtle"><?= nl2br(e($row['content'])) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="modal fade" id="deleteNewsModal" tabindex="-1" aria-labelledby="deleteNewsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteNewsModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteNewsTitle">this item</strong> item?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post">
                        <input type="hidden" name="mode" value="delete">
                        <input id="deleteNewsId" type="hidden" name="id" value="">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            document.addEventListener('DOMContentLoaded', function () {
                var formElement = document.getElementById('newsCreateForm');
                var formMode = document.getElementById('newsFormMode');
                var formId = document.getElementById('newsFormId');
                var formTitle = document.getElementById('title');
                var formContent = document.getElementById('content');
                var formPublishedAt = document.getElementById('published_at');
                var formIsPublished = document.getElementById('is_published');
                var formHeading = document.getElementById('newsFormTitle');
                var submitButton = document.getElementById('newsSubmitBtn');
                var cancelEditButton = document.getElementById('cancelEditBtn');

                function setCreateMode() {
                    formElement.reset();
                    formMode.value = 'create';
                    formId.value = '';
                    formHeading.textContent = 'Create News';
                    submitButton.textContent = 'Create News';
                    cancelEditButton.classList.add('d-none');
                    formIsPublished.checked = true;
                }

                document.querySelectorAll('.js-edit-news').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var payloadEncoded = button.getAttribute('data-news-payload') || '';
                        if (!payloadEncoded) {
                            return;
                        }

                        var payload = JSON.parse(atob(payloadEncoded));
                        formMode.value = 'update';
                        formId.value = String(payload.id || '');
                        formTitle.value = payload.title || '';
                        formContent.value = payload.content || '';
                        formPublishedAt.value = payload.published_at || '';
                        formIsPublished.checked = Number(payload.is_published) === 1;
                        formHeading.textContent = 'Edit News #' + String(payload.id || '');
                        submitButton.textContent = 'Update News';
                        cancelEditButton.classList.remove('d-none');

                        window.scrollTo({ top: formElement.getBoundingClientRect().top + window.scrollY - 90, behavior: 'smooth' });
                        setTimeout(function () {
                            formTitle.focus();
                        }, 220);
                    });
                });

                cancelEditButton.addEventListener('click', setCreateMode);

                var deleteModal = document.getElementById('deleteNewsModal');
                if (deleteModal) {
                    deleteModal.addEventListener('show.bs.modal', function (event) {
                        var trigger = event.relatedTarget;
                        if (!trigger) {
                            return;
                        }

                        var newsId = trigger.getAttribute('data-news-id') || '';
                        var newsTitle = trigger.getAttribute('data-news-title') || 'this item';
                        document.getElementById('deleteNewsId').value = newsId;
                        document.getElementById('deleteNewsTitle').textContent = newsTitle;
                    });
                }
            });
        })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
