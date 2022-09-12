import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import Link from './Link'

export default class LinkEvent extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public linkId: string

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => Link)
  public link: BelongsTo<typeof Link>
}
