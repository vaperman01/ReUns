</main>
<footer class="pie py-5 mt-5">
  <div class="contenedor">
    <div class="pie-grid">
      
      <div class="pie-col">
        <a href="<?= BASE ?>/index.php" class="d-inline-block mb-3">
          <img src="<?= BASE ?>/assets/img/logo.svg" alt="ReUns" width="140" height="40" class="pie-logo">
        </a>
        <p class="pie-desc">
          La plataforma universitaria para comprar, vender e intercambiar productos de forma segura. Precios en soles (S/).
        </p>
        <span class="pie-tag">
          🌱 Economía Circular
        </span>
      </div>

      <div class="pie-col">
        <h3>Plataforma</h3>
        <ul>
          <li><a href="<?= BASE ?>/index.php">Inicio</a></li>
          <li><a href="<?= BASE ?>/paginas/catalogo.php">Catálogo</a></li>
          <li><a href="<?= BASE ?>/paginas/catalogo.php?mod=intercambio">Intercambios</a></li>
          <li><a href="<?= BASE ?>/paginas/publicar.php">Publicar producto</a></li>
        </ul>
      </div>

      <div class="pie-col">
        <h3>Categorías</h3>
        <ul>
          <?php 
          $cat_pie = array_slice(categorias(), 0, 4, true);
          foreach ($cat_pie as $k => [$n, $i]): 
          ?>
            <li>
              <a href="<?= BASE ?>/paginas/catalogo.php?cat=<?= $k ?>">
                <?= $i ?> <?= e($n) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="pie-col">
        <h3>Legal y Ayuda</h3>
        <ul>
          <li><a href="<?= BASE ?>/paginas/terminos.php">Términos de uso</a></li>
          <li><a href="<?= BASE ?>/paginas/privacidad.php">Política de privacidad</a></li>
          <li><a href="<?= BASE ?>/paginas/faq.php">Preguntas frecuentes</a></li>
        </ul>
        <small class="pie-nota">
          * Los borradores legales se revisan antes del lanzamiento oficial.
        </small>
      </div>

    </div>

    <hr class="pie-linea">

    <div class="pie-bottom">
      <p>&copy; <?= date('Y') ?> <strong>ReUns</strong>. Todos los derechos reservados.</p>
      <p>Hecho para la comunidad universitaria 🎓</p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/bs/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<?php if (!empty($usr)): ?>
<script src="<?= BASE ?>/assets/js/contadores.js" data-api="<?= BASE ?>/paginas/notificaciones_api.php"></script>
<?php endif; ?>
<script src="<?= BASE ?>/assets/js/main.js"></script>
</body>
</html>