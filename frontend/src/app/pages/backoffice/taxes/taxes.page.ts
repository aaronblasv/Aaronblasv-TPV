import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IonContent } from '@ionic/angular/standalone';
import { SidebarComponent } from '../../../components/sidebar/sidebar.component';
import { TaxService } from '../../../services/api/tax.service';

@Component({
  selector: 'app-taxes',
  templateUrl: './taxes.page.html',
  styleUrls: ['./taxes.page.scss'],
  standalone: true,
  imports: [IonContent, CommonModule, FormsModule, SidebarComponent]
})
export class TaxesPage implements OnInit {

  taxes: any[] = [];
  showForm: boolean = false;
  editingTax: any = null;

  form = {
    name: '',
    percentage: 0,
  };

  constructor(private taxService: TaxService) {}

  ngOnInit() {
    this.loadTaxes();
  }

  loadTaxes() {
    this.taxService.getAll().subscribe({
      next: (data: any) => this.taxes = data,
      error: (err: any) => console.error(err)
    });
  }

  openForm(tax?: any) {
    if (tax) {
      this.editingTax = tax;
      this.form = { name: tax.name, percentage: tax.percentage };
    } else {
      this.editingTax = null;
      this.form = { name: '', percentage: 0 };
    }
    this.showForm = true;
  }

  closeForm() {
    this.showForm = false;
    this.editingTax = null;
  }

  save() {
    if (this.editingTax) {
      this.taxService.update(this.editingTax.uuid, this.form.name, this.form.percentage).subscribe({
        next: () => { this.loadTaxes(); this.closeForm(); },
        error: (err: any) => console.error(err)
      });
    } else {
      this.taxService.create(this.form.name, this.form.percentage).subscribe({
        next: () => { this.loadTaxes(); this.closeForm(); },
        error: (err: any) => console.error(err)
      });
    }
  }

  delete(uuid: string) {
    if (confirm('¿Estás seguro?')) {
      this.taxService.delete(uuid).subscribe({
        next: () => this.loadTaxes(),
        error: (err: any) => console.error(err)
      });
    }
  }
}
